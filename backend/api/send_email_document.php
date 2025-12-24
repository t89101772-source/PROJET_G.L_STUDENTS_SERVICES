<?php
/**
 * Endpoint pour envoyer un email avec le document en pièce jointe
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email_template.php';

// Vérifier que la connexion à la base de données est établie
if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Erreur de connexion à la base de données',
        'error' => 'Impossible de se connecter à la base de données'
    ]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

error_log("=== SEND EMAIL DOCUMENT ENDPOINT APPELÉ ===");
error_log("Method: $method");
error_log("URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    error_log("Input reçu: " . json_encode($input));
    
    $demandeId = $input['demande_id'] ?? null;
    error_log("Demande ID extrait: " . ($demandeId ?? 'NULL'));
    
    if (empty($demandeId) || !is_numeric($demandeId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de demande invalide']);
        exit;
    }
    
    try {
        // Récupérer les informations de la demande et de l'étudiant
        $stmt = $pdo->prepare("
            SELECT d.*, e.nom, e.prenom, e.email, e.cin, e.apogee_number
            FROM demande d
            LEFT JOIN etudiant e ON d.apogee_number = e.apogee_number
            WHERE d.id = ?
        ");
        $stmt->execute([$demandeId]);
        $demande = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$demande || empty($demande)) {
            http_response_code(404);
            echo json_encode(['error' => 'Demande non trouvée']);
            exit;
        }
        
        if ($demande['status'] !== 'Acceptée') {
            http_response_code(400);
            echo json_encode(['error' => 'La demande doit être acceptée pour envoyer l\'email']);
            exit;
        }
        
        // Vérifier que le document existe
        if (empty($demande['document_path'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Le document n\'a pas encore été généré. Veuillez d\'abord générer le document.'
            ]);
            exit;
        }
        
        // Construire le chemin absolu du PDF
        $document_path = ltrim($demande['document_path'], '/\\');
        $pdf_path = realpath(__DIR__ . '/../' . $document_path);
        
        error_log("Send email document - Demande ID: $demandeId");
        error_log("Send email document - Document path (DB): " . ($demande['document_path'] ?? 'VIDE'));
        error_log("Send email document - PDF path résolu: " . ($pdf_path ?: 'NON TROUVÉ'));
        error_log("Send email document - PDF existe: " . ($pdf_path && file_exists($pdf_path) ? 'OUI' : 'NON'));
        
        if (!$pdf_path || !file_exists($pdf_path)) {
            error_log("Send email document - ERREUR: PDF non trouvé pour demande ID: $demandeId");
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Le fichier PDF n\'existe pas sur le serveur',
                'document_path' => $demande['document_path'],
                'resolved_path' => $pdf_path ?: 'NON TROUVÉ'
            ]);
            exit;
        }
        
        // Vérifier que l'email de l'étudiant existe
        if (empty($demande['email'])) {
            error_log("Send email document - ERREUR: Email étudiant vide pour demande ID: $demandeId");
            http_response_code(400);
            echo json_encode(['error' => 'L\'email de l\'étudiant n\'est pas disponible']);
            exit;
        }
        
        // Récupérer le numéro d'attestation
        $numero_attestation = $demande['numero_attestation'] ?? ('ATT-' . date('Y') . '-' . str_pad($demandeId, 6, '0', STR_PAD_LEFT));
        
        if (empty($demande['numero_attestation'])) {
            $updateStmt = $pdo->prepare("UPDATE demande SET numero_attestation = ? WHERE id = ?");
            $updateStmt->execute([$numero_attestation, $demandeId]);
        }
        
        error_log("Send email document - Tentative d'envoi à: {$demande['email']} avec PDF: $pdf_path");
        
        $emailSent = sendEmailWithDocument(
            $demande['email'],
            $demande['nom'],
            $demande['prenom'],
            $numero_attestation,
            $demande['numero_demande'],
            $demande['document_type'],
            $pdf_path
        );
        
        error_log("Send email document - Résultat envoi: " . ($emailSent ? 'SUCCÈS' : 'ÉCHEC'));
        
        if ($emailSent) {
            // Mettre à jour email_sent
            try {
                $checkColumn = $pdo->query("SHOW COLUMNS FROM demande LIKE 'email_sent'");
                if ($checkColumn->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE demande ADD COLUMN email_sent TINYINT(1) DEFAULT 0");
                }
                $checkColumn = $pdo->query("SHOW COLUMNS FROM demande LIKE 'email_sent_at'");
                if ($checkColumn->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE demande ADD COLUMN email_sent_at DATETIME NULL");
                }
                $updateStmt = $pdo->prepare("UPDATE demande SET email_sent = 1, email_sent_at = NOW() WHERE id = ?");
                $updateStmt->execute([$demandeId]);
            } catch (PDOException $e) {
                // Ignorer si les colonnes n'existent pas
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Email envoyé avec succès',
                'numero_attestation' => $numero_attestation
            ]);
        } else {
            error_log("Send email document - ÉCHEC: sendEmailWithDocument a retourné false");
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de l\'envoi de l\'email',
                'message' => 'L\'email n\'a pas pu être envoyé. Vérifiez les logs du serveur pour plus de détails.',
                'demande_id' => $demandeId,
                'email' => $demande['email'] ?? 'N/A',
                'pdf_path' => $pdf_path ?? 'N/A'
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Send email document - EXCEPTION: ' . $e->getMessage());
        error_log('Send email document - TRACE: ' . $e->getTraceAsString());
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de l\'envoi de l\'email',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
} else {
    error_log("Send email document - Method non autorisée: $method");
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// S'assurer que le script se termine ici
exit;
?>

