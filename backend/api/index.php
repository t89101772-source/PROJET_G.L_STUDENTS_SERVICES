<?php
// Router simple pour l'API
// Les en-têtes CORS sont déjà définis dans index.php/router.php

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/api', '', $path);
$path = trim($path, '/');
$segments = explode('/', $path);

$endpoint = $segments[0] ?? '';

// Route les requêtes vers les bons fichiers
switch ($endpoint) {
    case 'auth':
        $_SERVER['PATH_INFO'] = '/' . ($segments[1] ?? '');
        require_once 'auth.php';
        break;
        
    case 'demandes':
        $_SERVER['PATH_INFO'] = '/' . implode('/', array_slice($segments, 1));
        require_once 'demandes.php';
        break;
        
    case 'reclamations':
        $_SERVER['PATH_INFO'] = '/' . implode('/', array_slice($segments, 1));
        require_once 'reclamations.php';
        break;
        
    case 'stats':
        $_SERVER['PATH_INFO'] = '/' . implode('/', array_slice($segments, 1));
        require_once 'stats.php';
        break;
        
    case 'chatbot':
        $_SERVER['PATH_INFO'] = '/' . implode('/', array_slice($segments, 1));
        require_once 'chatbot.php';
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}
?>

