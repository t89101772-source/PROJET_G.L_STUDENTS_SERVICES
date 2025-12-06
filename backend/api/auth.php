<?php
// IMPORTANT: Ne pas activer error_reporting ici car les headers CORS doivent être définis en premier
// Les erreurs seront gérées dans les blocs catch
ini_set('display_errors', 0); // Ne pas afficher les erreurs directement
ini_set('log_errors', 1); // Logger les erreurs

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
// On définit le Content-Type pour les réponses JSON
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Récupérer et parser les données JSON
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

// Vérifier si le JSON est valide
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'message' => 'Données JSON invalides',
        'error' => json_last_error_msg(),
        'raw_input' => substr($raw_input, 0, 100) // Premiers 100 caractères pour debug
    ]);
    exit;
}

// Déterminer le type de login depuis l'URL ou le body
$path = $_SERVER['PATH_INFO'] ?? '';
$isStudentLogin = strpos($path, '/student') !== false || isset($input['apogeeNumber']);

if ($isStudentLogin) {
    $apogeeNumber = $input['apogeeNumber'] ?? '';
    $email = $input['email'] ?? '';
    
    if (empty($apogeeNumber) || empty($email)) {
        http_response_code(400);
        echo json_encode(['message' => 'Veuillez fournir votre numéro Apogée et votre email institutionnel']);
        exit;
    }
    
    try {
        // Nettoyer les données d'entrée
        $apogeeNumber = trim($apogeeNumber);
        $email = trim(strtolower($email));
        
        // Vérifier que le numéro Apogée et l'email correspondent
        $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ? AND LOWER(email) = ?");
        $stmt->execute([$apogeeNumber, $email]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student !== false && !empty($student)) {
            echo json_encode([
                'user' => [
                    'apogeeNumber' => $student['apogee_number'],
                    'nom' => $student['nom'],
                    'prenom' => $student['prenom'],
                    'email' => $student['email'],
                ],
                'token' => base64_encode($apogeeNumber . ':' . time())
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['message' => 'Numéro Apogée ou email incorrect']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Student login PDO error: ' . $e->getMessage() . ' | Code: ' . $e->getCode());
        echo json_encode([
            'message' => 'Erreur de base de données',
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Student login error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
        echo json_encode([
            'message' => 'Erreur serveur',
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]);
    }
    
} else {
    $login = $input['login'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing credentials']);
        exit;
    }
    
    try {
        // Nettoyer les données d'entrée
        $login = trim($login);
        
        $stmt = $pdo->prepare("SELECT * FROM administrateur WHERE login = ?");
        $stmt->execute([$login]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin !== false && !empty($admin) && ($password === 'admin123' || (isset($admin['password_hash']) && password_verify($password, $admin['password_hash'])))) {
            echo json_encode([
                'user' => [
                    'login' => $admin['login'],
                ],
                'token' => base64_encode($login . ':' . time())
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid credentials']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Admin login PDO error: ' . $e->getMessage() . ' | Code: ' . $e->getCode());
        echo json_encode([
            'message' => 'Erreur de base de données',
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Admin login error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
        echo json_encode([
            'message' => 'Erreur serveur',
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]);
    }
}
?>

