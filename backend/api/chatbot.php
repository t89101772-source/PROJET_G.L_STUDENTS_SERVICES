<?php
require_once __DIR__ . '/../config/database.php';

// Vérifier que la connexion à la base de données est établie (si nécessaire)
// Le chatbot peut fonctionner sans BDD, donc on vérifie seulement si on en a besoin
if (isset($pdo) && $pdo === null) {
    // Si $pdo est explicitement null, il y a eu une erreur de connexion
    // Mais on continue quand même pour le mode fallback
}

// Les en-têtes CORS sont déjà définis dans index.php/router.php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get suggestions
    $userType = $_GET['user_type'] ?? 'guest';
    
    try {
        $stmt = $pdo->prepare("
            SELECT suggestion_text 
            FROM chat_suggestions 
            WHERE (user_type = ? OR user_type = 'all') AND is_active = TRUE 
            ORDER BY display_order ASC 
            LIMIT 4
        ");
        $stmt->execute([$userType]);
        $suggestions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode(['suggestions' => $suggestions]);
    } catch (PDOException $e) {
        echo json_encode(['suggestions' => []]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message vide']);
        exit;
    }
    
    // Simple fallback responses
    $messageLower = mb_strtolower($message, 'UTF-8');
    $response = '';
    
    if (preg_match('/\b(bonjour|salut|hello|bonsoir)\b/i', $message)) {
        $response = "Bonjour ! 👋 Je suis votre assistant virtuel. Comment puis-je vous aider aujourd'hui ?";
    } elseif (preg_match('/\b(demande|document|attestation|relevé|convention)\b/i', $message)) {
        $response = "Pour créer une demande de document, allez dans votre espace et cliquez sur 'Nouvelle Demande'. Vous pouvez demander: Attestation de scolarité, Attestation de réussite, Relevé de notes, Convention de stage, ou Autre document.";
    } elseif (preg_match('/\b(statut|état|suivre|suivi)\b/i', $message)) {
        $response = "Pour vérifier le statut de vos demandes, consultez votre tableau de bord. Vous y verrez toutes vos demandes avec leur statut: En attente, Acceptée, ou Refusée.";
    } elseif (preg_match('/\b(réclamation|reclamer|problème|plainte)\b/i', $message)) {
        $response = "Pour créer une réclamation, vous devez d'abord avoir une demande. Ensuite, allez dans la section 'Réclamations' et créez une nouvelle réclamation avec le motif et la description.";
    } elseif (preg_match('/\b(statistique|stats|donnée|nombre)\b/i', $message)) {
        try {
            $totalDemandes = $pdo->query("SELECT COUNT(*) FROM demande")->fetchColumn();
            $demandesEnAttente = $pdo->query("SELECT COUNT(*) FROM demande WHERE status = 'En attente'")->fetchColumn();
            $response = "📊 Statistiques actuelles:\n• Total demandes: $totalDemandes\n• Demandes en attente: $demandesEnAttente";
        } catch (Exception $e) {
            $response = "Je peux vous montrer les statistiques sur votre tableau de bord.";
        }
    } elseif (preg_match('/\b(merci|remercier|gracie)\b/i', $message)) {
        $response = "De rien ! 😊 N'hésitez pas si vous avez d'autres questions.";
    } elseif (preg_match('/\b(aide|help|assistance)\b/i', $message)) {
        $response = "Je peux vous aider avec:\n• Créer et suivre vos demandes\n• Gérer vos réclamations\n• Répondre à vos questions sur la plateforme\n• Vous guider dans l'utilisation du système";
    } else {
        $response = "Je comprends votre question. Pourriez-vous être plus précis ? Je peux vous aider avec les demandes, réclamations, ou toute autre question sur la plateforme.";
    }
    
    echo json_encode([
        'success' => true,
        'response' => $response
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>

