<?php
// Test direct de l'authentification
// Accès: http://localhost:8000/test_auth_direct.php

// Définir les en-têtes CORS
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

$results = [
    'test' => 'Authentification directe',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

// Test 1: Connexion à la base de données
try {
    require_once __DIR__ . '/config/database.php';
    if (isset($pdo) && $pdo !== null) {
        $results['tests']['database'] = ['status' => 'OK', 'message' => 'Connexion réussie'];
        
        // Test 2: Table etudiant
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM etudiant");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            $results['tests']['table_etudiant'] = [
                'status' => 'OK',
                'count' => $count['count']
            ];
            
            // Test avec données spécifiques
            $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ? AND LOWER(email) = ?");
            $stmt->execute(['A12345', 'douae.elassal@univ.ma']);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $results['tests']['student_lookup'] = [
                'status' => $student ? 'FOUND' : 'NOT_FOUND',
                'data' => $student ? [
                    'apogee_number' => $student['apogee_number'],
                    'email' => $student['email'],
                    'nom' => $student['nom'],
                    'prenom' => $student['prenom']
                ] : null
            ];
        } catch (PDOException $e) {
            $results['tests']['table_etudiant'] = [
                'status' => 'ERROR',
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
        
        // Test 3: Table administrateur
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM administrateur");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            $results['tests']['table_administrateur'] = [
                'status' => 'OK',
                'count' => $count['count']
            ];
            
            // Test avec données spécifiques
            $stmt = $pdo->prepare("SELECT * FROM administrateur WHERE login = ?");
            $stmt->execute(['admin']);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $results['tests']['admin_lookup'] = [
                'status' => $admin ? 'FOUND' : 'NOT_FOUND',
                'has_password_hash' => isset($admin['password_hash']),
                'login' => $admin ? $admin['login'] : null
            ];
        } catch (PDOException $e) {
            $results['tests']['table_administrateur'] = [
                'status' => 'ERROR',
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
        
        // Test 4: Simuler une requête POST comme le frontend
        $test_data = [
            'login' => 'admin',
            'password' => 'admin123'
        ];
        
        $results['tests']['simulated_request'] = [
            'test_data' => $test_data,
            'note' => 'Pour tester réellement, faites une requête POST vers /api/auth'
        ];
        
    } else {
        $results['tests']['database'] = [
            'status' => 'FAILED',
            'message' => 'Connexion échouée - $pdo est null'
        ];
    }
} catch (Exception $e) {
    $results['tests']['database'] = [
        'status' => 'ERROR',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

