<?php
/**
 * Endpoint pour valider les informations d'un étudiant (email, CIN, apogee)
 * Avant d'accéder au formulaire de demande
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $apogeeNumber = trim($input['apogee_number'] ?? '');
    $email = trim(strtolower($input['email'] ?? ''));
    $cin = trim($input['cin'] ?? '');
    
    // Validation des champs requis
    if (empty($apogeeNumber) || empty($email) || empty($cin)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Champs manquants',
            'message' => 'Tous les champs sont requis : email, numéro Apogée et CIN'
        ]);
        exit;
    }
    
    try {
        // Vérifier que les trois données (email, apogee_number, CIN) correspondent à un même étudiant
        $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ? AND LOWER(email) = ? AND cin = ?");
        $stmt->execute([$apogeeNumber, $email, $cin]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student || empty($student)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Données incorrectes',
                'message' => 'L\'email, le numéro Apogée et le CIN ne correspondent pas à un étudiant enregistré. Veuillez vérifier vos informations.',
                'valid' => false
            ]);
            exit;
        }
        
        // Validation réussie
        echo json_encode([
            'valid' => true,
            'message' => 'Validation réussie',
            'student' => [
                'apogee_number' => $student['apogee_number'],
                'nom' => $student['nom'],
                'prenom' => $student['prenom'],
                'email' => $student['email'],
                'cin' => $student['cin']
            ]
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Validate student error: ' . $e->getMessage());
        echo json_encode([
            'error' => 'Erreur de base de données',
            'message' => $e->getMessage(),
            'valid' => false
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Validate student error (general): ' . $e->getMessage());
        echo json_encode([
            'error' => 'Erreur lors de la validation',
            'message' => $e->getMessage(),
            'valid' => false
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>

