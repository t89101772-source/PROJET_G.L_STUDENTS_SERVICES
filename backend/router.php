<?php
// Simple router pour l'API
// Utilisez ce fichier comme point d'entrée si vous utilisez PHP built-in server
// Commande: php -S localhost:8000 router.php

// IMPORTANT: Définir les en-têtes CORS en PREMIER, avant toute sortie
// Cela inclut les warnings PHP, donc on désactive temporairement l'affichage
$old_error_reporting = error_reporting();
error_reporting(0); // Désactiver temporairement pour éviter toute sortie avant les headers

// Définir les en-têtes CORS - TOUJOURS, pour TOUTES les requêtes
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
header('Access-Control-Allow-Credentials: true');
header('Access-Max-Age: 86400'); // Cache preflight pour 24 heures

// Log pour debug PATCH
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    error_log("PATCH request - URI: " . ($_SERVER['REQUEST_URI'] ?? 'empty') . ", Method: " . $_SERVER['REQUEST_METHOD']);
}

// Réactiver le reporting d'erreurs
error_reporting($old_error_reporting);

// Gérer les requêtes preflight (OPTIONS) AVANT tout traitement
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    header('Content-Length: 0');
    exit(0);
}

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Gérer la page de vérification de document (accessible directement, pas via /api/)
if ($path === '/verify_document.php') {
    require_once __DIR__ . '/verify_document.php';
    exit;
}

// Gérer la racine
if ($path === '/' || $path === '') {
    header('Content-Type: application/json');
    echo json_encode([
        'message' => 'API Student Management System',
        'version' => '1.0',
        'endpoints' => [
            'POST /api/auth' => 'Authentification (étudiant ou admin)',
            'GET /api/demandes' => 'Liste des demandes',
            'GET /api/demandes/student/{apogee}' => 'Demandes d\'un étudiant',
            'GET /api/reclamations' => 'Liste des réclamations',
            'GET /api/stats' => 'Statistiques',
            'GET /verify_document.php?id=XXX' => 'Vérification de document via QR code'
        ]
    ]);
    exit;
}

// IMPORTANT: Intercepter TOUTES les requêtes vers /api/* 
// Même si les fichiers existent, on doit passer par router.php pour les en-têtes CORS
if (strpos($path, '/api/') === 0 || $path === '/api') {
    // Enlever le préfixe /api
    $path = preg_replace('#^/api#', '', $path);
    $path = trim($path, '/');
    $segments = explode('/', $path);
    
    $endpoint = $segments[0] ?? '';
    
    // Route les requêtes
    switch ($endpoint) {
        case 'auth':
            $_SERVER['PATH_INFO'] = '/' . ($segments[1] ?? '');
            require_once __DIR__ . '/api/auth.php';
            break;
            
        case 'demandes':
            // Construire le PATH_INFO correctement
            $remaining_path = implode('/', array_slice($segments, 1));
            $_SERVER['PATH_INFO'] = $remaining_path ? '/' . $remaining_path : '';
            error_log("Router - demandes: PATH_INFO = " . $_SERVER['PATH_INFO'] . ", segments = " . json_encode($segments));
            require_once __DIR__ . '/api/demandes.php';
            // Ne pas faire exit() ici pour laisser demandes.php gérer sa propre sortie
            break;
            
        case 'reclamations':
            $_SERVER['PATH_INFO'] = '/' . implode('/', array_slice($segments, 1));
            require_once __DIR__ . '/api/reclamations.php';
            break;
            
        case 'stats':
            $_SERVER['PATH_INFO'] = '/' . implode('/', array_slice($segments, 1));
            require_once __DIR__ . '/api/stats.php';
            break;

        case 'send-email-document':
                // Redirigé vers generate-document qui gère aussi l'envoi d'email
                $_SERVER['PATH_INFO'] = '/' . implode('/', array_slice($segments, 1));
                require_once __DIR__ . '/api/generate_document.php';
                break;

            case 'generate-document':
                $_SERVER['PATH_INFO'] = '/' . implode('/', array_slice($segments, 1));
                require_once __DIR__ . '/api/generate_document.php';
                break;

            case 'download-document':
                $_SERVER['PATH_INFO'] = '/' . implode('/', array_slice($segments, 1));
                require_once __DIR__ . '/api/download_document.php';
                break;

            case 'niveaux':
                require_once __DIR__ . '/api/niveaux.php';
                break;

            case 'annees':
                require_once __DIR__ . '/api/annees.php';
                break;

            default:
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Endpoint not found',
                'requested_path' => $path,
                'available_endpoints' => [
                    '/api/auth',
                    '/api/demandes',
                    '/api/reclamations',
                    '/api/stats',
                    '/api/chatbot'
                ],
                'note' => 'Assurez-vous que le serveur est lancé avec: php -S localhost:8000 router.php'
            ]);
            break;
    }
    exit;
}

// Gérer le téléchargement de documents (route spéciale, pas via /api/)
if ($path === '/download-document' || strpos($path, 'download-document') !== false) {
    require_once __DIR__ . '/api/download_document.php';
    exit;
}

// Pour les autres fichiers (test_connection.php, test_auth.php, verifier_serveur.php, etc.)
// Les servir MAIS avec les en-têtes CORS définis ci-dessus
if (file_exists(__DIR__ . $path) && !is_dir(__DIR__ . $path)) {
    // Servir le fichier mais avec les en-têtes CORS
    $file_path = __DIR__ . $path;
    $ext = pathinfo($file_path, PATHINFO_EXTENSION);
    
    // Définir le Content-Type approprié
    $content_types = [
        'php' => 'application/x-httpd-php',
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf'
    ];
    
    if (isset($content_types[$ext])) {
        header('Content-Type: ' . $content_types[$ext]);
    }
    
    // Inclure le fichier PHP ou servir le fichier statique
    if ($ext === 'php') {
        include $file_path;
    } else {
        readfile($file_path);
    }
    exit;
}

// Par défaut, 404
http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['error' => 'Not found']);
?>
