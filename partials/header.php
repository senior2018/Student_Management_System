<?php
// Shared header — included at the top of every page after login

$pageTitle = $pageTitle ?? 'Student Management System';
$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="topbar">
    <div class="brand"><a href="dashboard.php">SMS Admin</a></div>

    <?php if ($username != ''): ?>
    <nav class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="view_students.php">Students</a>
        <a href="add_student.php">Add Student</a>
        <a href="attendance.php">Attendance</a>
        <a href="view_attendance.php">Records</a>
        <a href="report.php" target="_blank">PDF Report</a>
        <span class="nav-user">Hi, <?php echo e($username); ?></span>
        <a class="btn-logout" href="logout.php">Logout</a>
    </nav>
    <?php endif; ?>
</header>

<main class="container">

<?php
// Show success / error message from the session (if any), then clear it
if (!empty($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . e($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}
if (!empty($_SESSION['error'])) {
    echo '<div class="alert alert-error">' . e($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}
?>
