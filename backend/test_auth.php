<?php
// Script de test pour l'authentification
// Accès: http://localhost:8000/test_auth.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

// Test 1: Connexion à la base de données
$results['tests']['database_connection'] = [];
try {
    require_once __DIR__ . '/config/database.php';
    if (isset($pdo) && $pdo !== null) {
        $results['tests']['database_connection'] = [
            'status' => 'SUCCESS',
            'message' => 'Connexion à la base de données réussie'
        ];
    } else {
        $results['tests']['database_connection'] = [
            'status' => 'FAILED',
            'message' => 'Connexion à la base de données échouée'
        ];
    }
} catch (Exception $e) {
    $results['tests']['database_connection'] = [
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

// Test 2: Vérifier la table etudiant
if (isset($pdo) && $pdo !== null) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM etudiant");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        $results['tests']['table_etudiant'] = [
            'status' => 'SUCCESS',
            'count' => $count['count']
        ];
        
        // Test avec les données de test
        $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ? AND email = ?");
        $stmt->execute(['A12345', 'douae.elassal@univ.ma']);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $results['tests']['test_student_data'] = [
            'status' => $student ? 'SUCCESS' : 'NOT_FOUND',
            'student_found' => $student !== false,
            'data' => $student
        ];
    } catch (PDOException $e) {
        $results['tests']['table_etudiant'] = [
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ];
    }
    
    // Test 3: Vérifier la table administrateur
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM administrateur");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        $results['tests']['table_administrateur'] = [
            'status' => 'SUCCESS',
            'count' => $count['count']
        ];
        
        // Test avec les données de test
        $stmt = $pdo->prepare("SELECT * FROM administrateur WHERE login = ?");
        $stmt->execute(['admin']);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $results['tests']['test_admin_data'] = [
            'status' => $admin ? 'SUCCESS' : 'NOT_FOUND',
            'admin_found' => $admin !== false,
            'has_password_hash' => isset($admin['password_hash'])
        ];
    } catch (PDOException $e) {
        $results['tests']['table_administrateur'] = [
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ];
    }
}

// Test 4: Simuler une requête POST
$results['tests']['simulate_post'] = [];
$test_data = [
    'login' => 'admin',
    'password' => 'admin123'
];

$results['tests']['simulate_post'] = [
    'test_data' => $test_data,
    'note' => 'Pour tester réellement, utilisez: curl -X POST http://localhost:8000/api/auth -H "Content-Type: application/json" -d \'{"login":"admin","password":"admin123"}\''
];

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

