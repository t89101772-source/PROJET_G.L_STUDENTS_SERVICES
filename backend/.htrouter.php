<?php
// Router pour le serveur PHP built-in
// Ce fichier est automatiquement utilisé par php -S quand router.php est spécifié
// IMPORTANT: Toutes les requêtes vers /api/* DOIVENT passer par router.php pour les en-têtes CORS

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// TOUJOURS router les requêtes vers /api/* vers router.php (pour les en-têtes CORS)
if (strpos($path, '/api/') === 0 || $path === '/api' || $path === '/') {
    $_SERVER['SCRIPT_NAME'] = '/router.php';
    require __DIR__ . '/router.php';
    return true;
}

// Pour les autres fichiers (comme test_connection.php, test_auth.php), les servir directement
if ($path !== '/' && file_exists(__DIR__ . $path) && !is_dir(__DIR__ . $path)) {
    return false; // Laisser PHP servir le fichier directement
}

// Par défaut, router vers router.php
$_SERVER['SCRIPT_NAME'] = '/router.php';
require __DIR__ . '/router.php';
return true;
?>

