<?php
// ====================================================================
// SHARED HEADER
// Included at the top of every page so the layout (nav bar, alerts,
// CSS link) stays consistent. Pages set $pageTitle, then include this.
// ====================================================================
 
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// If the page didn't set a title, fall back to a default.
$pageTitle = $pageTitle ?? 'Student Management System';

// Grab the current user (or null) so we can show their name in the nav.
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

<!-- Top navigation bar -->
<header class="topbar">
    <div class="brand"><a href="dashboard.php">SMS Admin</a></div>

    <?php if ($user): ?>
    <!-- Only show the menu when the user is logged in -->
    <nav class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="view_students.php">Students</a>
        <a href="add_student.php">Add Student</a>
        <a href="attendance.php">Attendance</a>
        <a href="view_attendance.php">Records</a>
        <span class="nav-user">Hi, <?= e($user['username']) ?></span>
        <a class="btn-logout" href="logout.php">Logout</a>
    </nav>
    <?php endif; ?>
</header>

<main class="container">

<?php
// Show flash messages (set by other pages right before redirecting here).
// flash_get() returns the message AND clears it, so it only appears once.
?>
<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash_get('error')): ?>
    <div class="alert alert-error"><?= e($msg) ?></div>
<?php endif; ?>
