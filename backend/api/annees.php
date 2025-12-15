<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {
    // Récupérer toutes les années uniques depuis les inscriptions ou resultat_annee
    $stmt = $pdo->query("
        SELECT DISTINCT annee_universitaire 
        FROM inscription 
        ORDER BY annee_universitaire DESC
    ");
    $annees = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Si pas d'années dans inscription, chercher dans resultat_annee
    if (empty($annees)) {
        $stmt = $pdo->query("
            SELECT DISTINCT annee_universitaire 
            FROM resultat_annee 
            ORDER BY annee_universitaire DESC
        ");
        $annees = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    echo json_encode([
        'success' => true,
        'annees' => $annees
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des années',
        'message' => $e->getMessage()
    ]);
}
?>

