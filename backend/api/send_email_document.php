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

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $demandeId = $input['demande_id'] ?? null;
    
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
            echo json_encode(['error' => 'Le document n\'a pas encore été généré. Veuillez d\'abord générer le document.']);
            exit;
        }
        
        $pdf_path = __DIR__ . '/../' . $demande['document_path'];
        
        if (!file_exists($pdf_path)) {
            http_response_code(404);
            echo json_encode(['error' => 'Le fichier PDF n\'existe pas sur le serveur']);
            exit;
        }
        
        // Vérifier que l'email de l'étudiant existe
        if (empty($demande['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'L\'email de l\'étudiant n\'est pas disponible']);
            exit;
        }
        
        // Récupérer le numéro d'attestation
        $numero_attestation = $demande['numero_attestation'] ?? ('ATT-' . date('Y') . '-' . str_pad($demandeId, 6, '0', STR_PAD_LEFT));
        
        // Si le numéro d'attestation n'existe pas encore, le créer et le sauvegarder
        if (empty($demande['numero_attestation'])) {
            $updateStmt = $pdo->prepare("UPDATE demande SET numero_attestation = ? WHERE id = ?");
            $updateStmt->execute([$numero_attestation, $demandeId]);
        }
        
        // Envoyer l'email avec le document
        error_log("Send email document - Tentative d'envoi pour demande ID: $demandeId, email: " . $demande['email']);
        
        $emailSent = sendEmailWithDocument(
            $demande['email'],
            $demande['nom'],
            $demande['prenom'],
            $numero_attestation,
            $demande['numero_demande'],
            $demande['document_type'],
            $pdf_path
        );
        
        if ($emailSent) {
            error_log("Send email document - Email envoyé avec succès pour demande ID: $demandeId");
            
            // Mettre à jour email_sent et email_sent_at
            try {
                // Vérifier si les colonnes existent, sinon les ajouter
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
                error_log("Send email document - email_sent mis à jour pour demande ID: $demandeId");
            } catch (PDOException $e) {
                error_log('Error updating email_sent: ' . $e->getMessage());
                // Continuer même si la mise à jour échoue
            }
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Email envoyé avec succès',
                'numero_attestation' => $numero_attestation
            ]);
        } else {
            error_log("Send email document - Échec de l'envoi d'email pour demande ID: $demandeId");
            http_response_code(500);
            echo json_encode([
                'error' => 'Erreur lors de l\'envoi de l\'email',
                'message' => 'L\'email n\'a pas pu être envoyé. Vérifiez les logs du serveur.'
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Send email document error: ' . $e->getMessage());
        echo json_encode([
            'error' => 'Erreur lors de l\'envoi de l\'email',
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>

