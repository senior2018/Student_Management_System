<?php
// Shared header — added to the top of every page so the nav bar and
// layout are the same everywhere. Each page sets $pageTitle before including this.

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Default title if the page didn't pick one
$pageTitle = $pageTitle ?? 'Student Management System';

// Get the logged-in user so we can show their name in the nav
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- Top nav bar -->
<header class="topbar">
    <div class="brand"><a href="dashboard.php">SMS Admin</a></div>

    <?php if ($user): ?>
    <!-- Only show menu when someone is logged in -->
    <nav class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="view_students.php">Students</a>
        <a href="add_student.php">Add Student</a>
        <a href="attendance.php">Attendance</a>
        <a href="view_attendance.php">Records</a>
        <a href="report.php" target="_blank">PDF Report</a>
        <span class="nav-user">Hi, <?= e($user['username']) ?></span>
        <a class="btn-logout" href="logout.php">Logout</a>
    </nav>
    <?php endif; ?>
</header>

<main class="container">

<?php
// Show any flash messages (set right before a redirect happens).
// flash_get() returns the message AND removes it, so it only shows once.
?>
<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash_get('error')): ?>
    <div class="alert alert-error"><?= e($msg) ?></div>
<?php endif; ?>
