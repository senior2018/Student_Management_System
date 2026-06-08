<?php
// Database connection.
// Every page that needs the database does: include "includes/db.php";
// After that, the $pdo variable is ready to use.

$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    die("Missing includes/config.php. Copy includes/config.example.php and add your password.");
}
$config = require $configPath;
$db = $config['db'];

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4",
        $db['user'],
        $db['password']
    );
    // Throw errors instead of failing silently
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
