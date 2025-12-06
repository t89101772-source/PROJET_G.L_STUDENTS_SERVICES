<?php
// Script de vérification du serveur
// Accès: http://localhost:8000/verifier_serveur.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$results = [
    'server_info' => [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'request_uri' => $_SERVER['REQUEST_URI'],
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown',
    ],
    'cors_check' => [],
    'router_check' => []
];

// Vérifier les en-têtes CORS
$cors_headers = [
    'Access-Control-Allow-Origin',
    'Access-Control-Allow-Methods',
    'Access-Control-Allow-Headers',
    'Access-Control-Allow-Credentials'
];

$results['cors_check'] = [
    'note' => 'Les en-têtes CORS doivent être définis dans router.php',
    'expected_headers' => $cors_headers,
    'current_headers' => []
];

foreach ($cors_headers as $header) {
    $value = headers_list();
    $found = false;
    foreach ($value as $h) {
        if (stripos($h, $header) === 0) {
            $found = true;
            $results['cors_check']['current_headers'][$header] = $h;
            break;
        }
    }
    if (!$found) {
        $results['cors_check']['current_headers'][$header] = 'NOT SET';
    }
}

// Vérifier si on passe par router.php
// Quand router.php sert un fichier, $_SERVER['SCRIPT_NAME'] peut être le fichier servi
// Mais on peut vérifier si les en-têtes CORS sont définis (ce qui indique qu'on passe par router.php)
$cors_origin = false;
foreach (headers_list() as $header) {
    if (stripos($header, 'Access-Control-Allow-Origin') === 0) {
        $cors_origin = true;
        break;
    }
}

$results['router_check'] = [
    'script_name' => $_SERVER['SCRIPT_NAME'],
    'request_uri' => $_SERVER['REQUEST_URI'],
    'is_router' => $cors_origin && (
        strpos($_SERVER['SCRIPT_NAME'], 'router.php') !== false || 
        strpos($_SERVER['PHP_SELF'], 'router.php') !== false ||
        isset($_SERVER['HTTP_X_ROUTED']) // On peut ajouter un header custom
    ),
    'cors_headers_present' => $cors_origin,
    'note' => 'Si is_router est false OU cors_headers_present est false, le serveur ne passe pas par router.php correctement'
];

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

