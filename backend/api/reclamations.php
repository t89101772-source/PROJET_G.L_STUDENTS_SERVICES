<?php
require_once __DIR__ . '/../config/database.php';

// Vérifier que la connexion à la base de données est établie
if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Erreur de connexion à la base de données',
        'error' => 'Impossible de se connecter à la base de données. Vérifiez la configuration dans config/database.php'
    ]);
    exit;
}

// Les en-têtes CORS sont déjà définis dans index.php/router.php
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

if ($method === 'GET') {
    if (strpos($path, '/student/') !== false) {
        $apogeeNumber = basename($path);
        $stmt = $pdo->prepare("
            SELECT r.*, d.document_type 
            FROM reclamation r
            JOIN demande d ON r.demande_id = d.id
            WHERE d.apogee_number = ?
            ORDER BY r.date_reclamation DESC
        ");
        $stmt->execute([$apogeeNumber]);
        echo json_encode($stmt->fetchAll());
    } else {
        $stmt = $pdo->query("
            SELECT r.*, d.document_type, e.nom, e.prenom
            FROM reclamation r
            LEFT JOIN demande d ON r.demande_id = d.id
            LEFT JOIN etudiant e ON d.apogee_number = e.apogee_number
            ORDER BY r.date_reclamation DESC
        ");
        echo json_encode($stmt->fetchAll());
    }
    
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $demandeId = $input['demande_id'] ?? '';
    $motif = $input['motif'] ?? '';
    $description = $input['description'] ?? '';
    
    if (empty($demandeId) || empty($motif) || empty($description)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO reclamation (demande_id, motif, description, status) VALUES (?, ?, ?, 'Ouverte')");
        $stmt->execute([$demandeId, $motif, $description]);
        
        echo json_encode([
            'success' => true,
            'id' => $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    
} elseif ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Le path peut être /{id}/respond ou /{id}/close
    // Exemple: /1/respond ou /1/close
    $path_clean = trim($path, '/');
    $path_parts = $path_clean ? explode('/', $path_clean) : [];
    $id = $path_parts[0] ?? null;
    $action = $path_parts[1] ?? '';
    
    if (empty($id) || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid reclamation ID']);
        exit;
    }
    
    if ($action === 'respond') {
        $reponse = $input['reponse'] ?? '';
        
        if (empty($reponse)) {
            http_response_code(400);
            echo json_encode(['error' => 'Response is required']);
            exit;
        }
        
        try {
            // Vérifier que la réclamation existe
            $checkStmt = $pdo->prepare("SELECT id FROM reclamation WHERE id = ?");
            $checkStmt->execute([$id]);
            if (!$checkStmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Reclamation not found']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE reclamation SET status = 'Fermée', reponse_admin = ? WHERE id = ?");
            $stmt->execute([$reponse, $id]);
            echo json_encode(['success' => true, 'message' => 'Reclamation responded successfully']);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Respond reclamation error: ' . $e->getMessage());
            echo json_encode([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ]);
        }
    } elseif ($action === 'close') {
        try {
            // Vérifier que la réclamation existe
            $checkStmt = $pdo->prepare("SELECT id FROM reclamation WHERE id = ?");
            $checkStmt->execute([$id]);
            if (!$checkStmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Reclamation not found']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE reclamation SET status = 'Fermée' WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Reclamation closed successfully']);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Close reclamation error: ' . $e->getMessage());
            echo json_encode([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Use /respond or /close']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>

