<?php
// Point d'entrée principal pour l'API
// Ce fichier est utilisé quand on lance: php -S localhost:8000

// Définir les en-têtes CORS en premier (avant toute sortie)
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Gérer les requêtes preflight (OPTIONS) AVANT tout traitement
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Router vers router.php pour le routage des requêtes
require_once __DIR__ . '/router.php';
?>

