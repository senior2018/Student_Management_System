<?php
// ====================================================================
// ONE-TIME SETUP SCRIPT
// Open in browser ONCE (http://localhost:8000/setup.php) to:
//   1. Create the `student` database if it doesn't exist
//   2. Create the users, students, attendance tables
//   3. Create the default admin user (admin / admin123)
// After it succeeds, DELETE this file for security.
// ====================================================================
 
declare(strict_types=1);

// Load DB credentials from config.php (must exist before this works)
$configPath = __DIR__ . '/includes/config.php';
if (!file_exists($configPath)) {
    die('Missing includes/config.php — copy includes/config.example.php to includes/config.php and set your DB password first.');
}
$config = require $configPath;
$db = $config['db'];
 
// $log holds messages we want to display to the user as the script runs
$log = [];

try {
    // Connect WITHOUT a database first (no "dbname=" in the DSN) — that
    // way we can CREATE the database if it doesn't exist yet.
    $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $db['host'], $db['port'], $db['charset']);
    $pdo = new PDO($dsn, $db['user'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $log[] = "Connected to MySQL at {$db['host']}:{$db['port']}.";

    // Note the backticks around the DB name — they let us use names that
    // would otherwise be reserved words.
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$db['name']}`");
    $log[] = "Using database `{$db['name']}`.";

    // ----- TABLE: users (admin accounts) -----
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $log[] = "Table `users` ready.";

    // ----- TABLE: students -----
    // ENUM restricts the column to a fixed set of allowed values
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INT PRIMARY KEY AUTO_INCREMENT,
            fullname VARCHAR(100) NOT NULL,
            gender ENUM('Male','Female','Other') NOT NULL,
            course VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $log[] = "Table `students` ready.";

    // ----- TABLE: attendance -----
    //   FOREIGN KEY links student_id to students.id
    //   ON DELETE CASCADE = deleting a student also deletes their attendance
    //   UNIQUE KEY (student_id, attend_date) = can't mark the same person twice on the same day
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attendance (
            id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT NOT NULL,
            attend_date DATE NOT NULL,
            status ENUM('Present','Absent','Late') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY uniq_student_date (student_id, attend_date)
        )
    ");
    $log[] = "Table `attendance` ready.";

    // ----- SEED: default admin (only if not already there) -----
    $check = $pdo->prepare('SELECT id FROM users WHERE username = :u');
    $check->execute([':u' => 'admin']);
    if (!$check->fetch()) {
        // password_hash() turns the plain password into a secure one-way hash
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $ins = $pdo->prepare('INSERT INTO users (username, password) VALUES (:u, :p)');
        $ins->execute([':u' => 'admin', ':p' => $hash]);
        $log[] = "Default admin created — username: <strong>admin</strong>, password: <strong>admin123</strong>.";
    } else {
        $log[] = "Admin user already exists (skipped seed).";
    }

    $success = true;
} catch (PDOException $e) {
    // If anything fails (wrong password, MySQL down, etc.) we catch it here
    $success = false;
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup — Student Management System</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Setup</h1>
        <?php if ($success): ?>
            <div class="alert alert-success"><strong>Setup complete!</strong></div>
            <ul>
                <?php foreach ($log as $line): ?>
                    <li><?= $line ?></li>
                <?php endforeach; ?>
            </ul>
            <div class="alert alert-error">
                <strong>Important:</strong> delete <code>setup.php</code> now so nobody else can re-run it.
            </div>
            <p>
                <a class="btn" href="login.php">Go to Login →</a>
            </p>
        <?php else: ?>
            <div class="alert alert-error">
                <strong>Setup failed:</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <p>Common causes:</p>
            <ul>
                <li>Wrong password in <code>includes/config.php</code></li>
                <li>MySQL not running (try <code>brew services start mysql</code>)</li>
                <li>User <code><?= htmlspecialchars($db['user']) ?></code> doesn't have permission to create databases</li>
            </ul>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
