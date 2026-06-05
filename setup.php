<?php
// One-time setup script.
// Open this in your browser ONCE (http://localhost:8080/setup.php) to:
//   1. Create the `student` database (if it doesn't exist yet)
//   2. Create the users, students, attendance, and grades tables
//   3. Create the default admin user (admin / admin123)
// After it works, DELETE this file so nobody can re-run it.

$configPath = __DIR__ . '/includes/config.php';
if (!file_exists($configPath)) {
    die('Missing includes/config.php. Copy includes/config.example.php to includes/config.php and add your password first.');
}
$config = require $configPath;
$db = $config['db'];

// $log holds messages we want to show the user as the script runs
$log = [];

try {
    // Connect WITHOUT picking a database (no "dbname=" in the DSN).
    // We do this so we can CREATE the database if it doesn't exist.
    $dsn = "mysql:host={$db['host']};port={$db['port']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['user'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $log[] = "Connected to MySQL at {$db['host']}:{$db['port']}.";

    // Backticks around the DB name = lets us use names that would
    // otherwise be reserved words.
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$db['name']}`");
    $log[] = "Using database `{$db['name']}`.";

    // ===== users (admin accounts) =====
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $log[] = "Table `users` ready.";

    // ===== students =====
    // ENUM means the column only accepts the listed values.
    // `photo` is the filename (the file lives in /uploads/).
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INT PRIMARY KEY AUTO_INCREMENT,
            fullname VARCHAR(100) NOT NULL,
            gender ENUM('Male','Female','Other') NOT NULL,
            course VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            photo VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    // If `students` already existed without `photo`, add it now.
    // try/catch because "column already exists" would otherwise stop setup.
    try { $pdo->exec("ALTER TABLE students ADD COLUMN photo VARCHAR(255) NULL"); } catch (PDOException $ignore) {}
    $log[] = "Table `students` ready.";

    // ===== grades (for GPA) =====
    //   subject: course name (e.g. "Math 101")
    //   score:   0-100 grade
    //   credits: course weight (1, 2, 3, 4...)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS grades (
            id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT NOT NULL,
            subject VARCHAR(100) NOT NULL,
            score DECIMAL(5,2) NOT NULL,
            credits TINYINT UNSIGNED NOT NULL DEFAULT 3,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        )
    ");
    $log[] = "Table `grades` ready.";

    // ===== attendance =====
    //   FOREIGN KEY links student_id to students.id
    //   ON DELETE CASCADE = removing a student also removes their attendance
    //   UNIQUE KEY = can't mark a student twice on the same day
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

    // ===== seed default admin (only if not already there) =====
    $check = $pdo->prepare('SELECT id FROM users WHERE username = :u');
    $check->execute([':u' => 'admin']);
    if (!$check->fetch()) {
        // password_hash turns the plain password into a safe one-way string
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $ins = $pdo->prepare('INSERT INTO users (username, password) VALUES (:u, :p)');
        $ins->execute([':u' => 'admin', ':p' => $hash]);
        $log[] = "Default admin created — username: <strong>admin</strong>, password: <strong>admin123</strong>.";
    } else {
        $log[] = "Admin user already exists (skipped).";
    }

    $success = true;
} catch (PDOException $e) {
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
