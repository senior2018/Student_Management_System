<?php
// This file connects us to the MySQL database.
// Any other file that needs the database just calls db() to get the connection.

function db() {
    // We remember the connection in this static variable, so we only
    // connect once even if db() is called many times in the same page.
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    // Load the config file (which has our DB password)
    $configPath = __DIR__ . '/config.php';
    if (!file_exists($configPath)) {
        die('Missing includes/config.php. Copy includes/config.example.php to includes/config.php and add your password.');
    }
    $config = require $configPath;
    $db = $config['db'];

    // Build the DSN string — this tells PDO how to connect
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";

    try {
        // Make the actual connection
        $pdo = new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // throw error on problems
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // give us results by column name
            PDO::ATTR_EMULATE_PREPARES   => false,                   // use real prepared statements
        ]);
    } catch (PDOException $e) {
        die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
    }

    return $pdo;
}
