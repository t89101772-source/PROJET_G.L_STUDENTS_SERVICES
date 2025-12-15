<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query("SELECT id, code, nom, numero_semestre, annee_academique, type_cycle, annee_cycle, semestre_annee FROM niveau ORDER BY numero_semestre ASC");
    $niveaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'niveaux' => $niveaux
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des niveaux',
        'message' => $e->getMessage()
    ]);
}
?>

