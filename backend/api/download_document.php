<?php
/**
 * Téléchargement de documents PDF
 */

// IMPORTANT: Définir les en-têtes CORS en PREMIER
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
header('Access-Control-Allow-Credentials: true');

// Gérer les requêtes preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    header('Content-Length: 0');
    exit(0);
}

require_once __DIR__ . '/../config/database.php';

// Vérifier que la connexion à la base de données est établie
if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'message' => 'Erreur de connexion à la base de données',
        'error' => 'Impossible de se connecter à la base de données'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $demandeId = $_GET['demande_id'] ?? null;
    $apogeeNumber = $_GET['apogee_number'] ?? null; // Pour vérifier que l'étudiant peut télécharger
    
    if (empty($demandeId) || !is_numeric($demandeId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de demande invalide']);
        exit;
    }
    
    try {
        // Récupérer les informations de la demande
        $stmt = $pdo->prepare("
            SELECT d.*, e.apogee_number as student_apogee
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
        
        // Vérifier que l'étudiant peut télécharger (si apogee_number est fourni)
        if ($apogeeNumber && $demande['student_apogee'] !== $apogeeNumber) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès non autorisé']);
            exit;
        }
        
        // Vérifier que le document existe
        if (empty($demande['document_path'])) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Document non généré']);
            exit;
        }
        
        $filepath = __DIR__ . '/../' . $demande['document_path'];
        
        if (!file_exists($filepath)) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Fichier non trouvé',
                'expected_path' => $filepath,
                'document_path' => $demande['document_path']
            ]);
            exit;
        }
        
        // Envoyer le fichier
        $filename = basename($filepath);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        readfile($filepath);
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Download document error: ' . $e->getMessage());
        echo json_encode([
            'error' => 'Erreur lors du téléchargement',
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>

