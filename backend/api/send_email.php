<?php
/**
 * Service d'envoi d'emails
 * 
 * NOTE: Pour un environnement de production, configurez un service d'email réel
 * (PHPMailer, SendGrid, Mailgun, etc.)
 */

require_once __DIR__ . '/../config/database.php';

// Vérifier que la connexion à la base de données est établie
if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Erreur de connexion à la base de données',
        'error' => 'Impossible de se connecter à la base de données'
    ]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $to = $input['to'] ?? '';
    $subject = $input['subject'] ?? '';
    $message = $input['message'] ?? '';
    $demandeId = $input['demande_id'] ?? null;
    
    if (empty($to) || empty($subject) || empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tous les champs sont requis (to, subject, message)']);
        exit;
    }
    
    try {
        // En production, utilisez un vrai service d'email
        // Pour l'instant, on simule l'envoi et on enregistre dans la base de données
        
        // Mettre à jour le statut email_sent dans la table demande si demande_id est fourni
        if ($demandeId) {
            $stmt = $pdo->prepare("UPDATE demande SET email_sent = TRUE, email_sent_at = NOW() WHERE id = ?");
            $stmt->execute([$demandeId]);
        }
        
        // Simuler l'envoi d'email
        // En production, remplacez ceci par un vrai service d'email
        $emailSent = true; // Simulé
        
        // Log de l'email (pour debug)
        error_log("Email envoyé à: $to | Sujet: $subject");
        
        echo json_encode([
            'success' => true,
            'message' => 'Email envoyé avec succès',
            'to' => $to,
            'subject' => $subject,
            'note' => 'En production, configurez un service d\'email réel (PHPMailer, SendGrid, etc.)'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Send email error: ' . $e->getMessage());
        echo json_encode([
            'error' => 'Erreur lors de l\'envoi de l\'email',
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>

