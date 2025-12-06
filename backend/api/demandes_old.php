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
        // Get demandes by student
        $apogeeNumber = basename($path);
        $stmt = $pdo->prepare("SELECT * FROM demande WHERE apogee_number = ? ORDER BY date_demande DESC");
        $stmt->execute([$apogeeNumber]);
        echo json_encode($stmt->fetchAll());
    } else {
        // Get all demandes
        $stmt = $pdo->query("
            SELECT d.*, e.nom, e.prenom 
            FROM demande d
            LEFT JOIN etudiant e ON d.apogee_number = e.apogee_number
            ORDER BY d.date_demande DESC
        ");
        echo json_encode($stmt->fetchAll());
    }
    
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $apogeeNumber = $input['apogee_number'] ?? '';
    $documentType = $input['document_type'] ?? '';
    
    if (empty($apogeeNumber) || empty($documentType)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO demande (apogee_number, document_type, status) VALUES (?, ?, 'En attente')");
        $stmt->execute([$apogeeNumber, $documentType]);
        
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
    
    // Le path peut être /{id}/status ou juste /{id}
    // Exemple: /1/status ou /1
    $path_clean = trim($path, '/');
    $path_parts = $path_clean ? explode('/', $path_clean) : [];
    $id = $path_parts[0] ?? null;
    $action = $path_parts[1] ?? '';
    
    // Debug: logger le path pour comprendre
    // error_log("PATCH request - Path: $path, Parts: " . json_encode($path_parts) . ", ID: $id, Action: $action");
    
    if ($action === 'status' && $id) {
        $status = $input['status'] ?? '';
        $justification = $input['justification'] ?? null;
        
        if (empty($status)) {
            http_response_code(400);
            echo json_encode(['error' => 'Status is required']);
            exit;
        }
        
        if (empty($id) || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid demande ID']);
            exit;
        }
        
        try {
            // Vérifier que la demande existe
            $checkStmt = $pdo->prepare("SELECT id FROM demande WHERE id = ?");
            $checkStmt->execute([$id]);
            if (!$checkStmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Demande not found']);
                exit;
            }
            
            // Mettre à jour le statut
            if (!empty($justification) && $justification !== null) {
                $stmt = $pdo->prepare("UPDATE demande SET status = ?, justification_refus = ? WHERE id = ?");
                $stmt->execute([$status, $justification, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE demande SET status = ?, justification_refus = NULL WHERE id = ?");
                $stmt->execute([$status, $id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Demande updated successfully']);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Update demande error: ' . $e->getMessage());
            echo json_encode([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request path']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>

