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

$path = $_SERVER['PATH_INFO'] ?? '';

if (strpos($path, '/student/') !== false) {
    $apogeeNumber = basename($path);
    
    try {
        $total = $pdo->prepare("SELECT COUNT(*) FROM demande WHERE apogee_number = ?");
        $total->execute([$apogeeNumber]);
        $totalDemandes = $total->fetchColumn();
        
        $pending = $pdo->prepare("SELECT COUNT(*) FROM demande WHERE apogee_number = ? AND status = 'En attente'");
        $pending->execute([$apogeeNumber]);
        $demandesEnAttente = $pending->fetchColumn();
        
        $accepted = $pdo->prepare("SELECT COUNT(*) FROM demande WHERE apogee_number = ? AND status = 'Acceptée'");
        $accepted->execute([$apogeeNumber]);
        $demandesAcceptees = $accepted->fetchColumn();
        
        $reclamations = $pdo->prepare("
            SELECT COUNT(*) 
            FROM reclamation r
            JOIN demande d ON r.demande_id = d.id
            WHERE d.apogee_number = ?
        ");
        $reclamations->execute([$apogeeNumber]);
        $totalReclamations = $reclamations->fetchColumn();
        
        echo json_encode([
            'total_demandes' => (int)$totalDemandes,
            'demandes_en_attente' => (int)$demandesEnAttente,
            'demandes_acceptees' => (int)$demandesAcceptees,
            'total_reclamations' => (int)$totalReclamations
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    
} else {
    // Admin stats
    try {
        $totalDemandes = $pdo->query("SELECT COUNT(*) FROM demande")->fetchColumn();
        $demandesEnAttente = $pdo->query("SELECT COUNT(*) FROM demande WHERE status = 'En attente'")->fetchColumn();
        // Les demandes acceptées incluent "Acceptée" et "Traitée" (car Traitée = acceptée et email envoyé)
        $demandesAcceptees = $pdo->query("SELECT COUNT(*) FROM demande WHERE status IN ('Acceptée', 'Traitée')")->fetchColumn();
        $demandesRefusees = $pdo->query("SELECT COUNT(*) FROM demande WHERE status = 'Refusée'")->fetchColumn();
        $totalReclamations = $pdo->query("SELECT COUNT(*) FROM reclamation")->fetchColumn();
        // Réclamations ouvertes = celles qui ne sont pas "Résolue" ou "Rejetée" ou "Fermée"
        $reclamationsOuvertes = $pdo->query("SELECT COUNT(*) FROM reclamation WHERE status NOT IN ('Résolue', 'Rejetée', 'Fermée')")->fetchColumn();
        
        echo json_encode([
            'total_demandes' => (int)$totalDemandes,
            'demandes_en_attente' => (int)$demandesEnAttente,
            'demandes_acceptees' => (int)$demandesAcceptees,
            'demandes_refusees' => (int)$demandesRefusees,
            'total_reclamations' => (int)$totalReclamations,
            'reclamations_ouvertes' => (int)$reclamationsOuvertes
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}
?>

