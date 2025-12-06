<?php
// Script de test pour vérifier la connexion à la base de données
// Accès: http://localhost:8000/test_connection.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host = 'localhost';
$dbname = 'student_admin_db';
$username = 'root';
$password = '';

$results = [
    'php_version' => phpversion(),
    'pdo_available' => extension_loaded('pdo'),
    'pdo_mysql_available' => extension_loaded('pdo_mysql'),
    'connection_test' => null,
    'database_test' => null,
    'tables_test' => null
];

// Test de connexion
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    $results['connection_test'] = 'SUCCESS';
    
    // Test de la table etudiant
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM etudiant");
        $count = $stmt->fetch();
        $results['database_test'] = [
            'status' => 'SUCCESS',
            'student_count' => $count['count']
        ];
        
        // Test d'une requête spécifique
        $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ? AND email = ?");
        $stmt->execute(['A12345', 'douae.elassal@univ.ma']);
        $student = $stmt->fetch();
        
        $results['tables_test'] = [
            'status' => 'SUCCESS',
            'student_found' => $student !== false,
            'student_data' => $student ? [
                'apogee_number' => $student['apogee_number'],
                'email' => $student['email'],
                'nom' => $student['nom'],
                'prenom' => $student['prenom']
            ] : null
        ];
        
    } catch (PDOException $e) {
        $results['database_test'] = [
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ];
    }
    
} catch (PDOException $e) {
    $results['connection_test'] = [
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT);
?>

