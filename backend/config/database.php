<?php
// Ne pas définir de header ici, laisser les fichiers API le faire
// Cela évite les conflits d'en-têtes

// Configuration de la base de données
// Modifiez ces valeurs selon votre configuration MySQL
$host = '127.0.0.1'; // ou '127.0.0.1'
$dbname = 'student_admin_db';
$username = 'root';
$password = ''; // Mettez votre mot de passe MySQL si nécessaire

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ne pas faire exit ici, laisser les fichiers API gérer l'erreur
    // Mais définir $pdo à null pour indiquer l'échec
    $pdo = null;
    error_log('Database connection failed: ' . $e->getMessage());
}
?>

