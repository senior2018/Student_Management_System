<?php
// Dashboard — the page admins see after logging in

require "includes/auth.php";
require_login();
require "includes/db.php";
require "includes/mail.php";

// Is email turned on?
$emailReady = mail_config() !== null;

// Count all students
$stmt = $pdo->query("SELECT COUNT(*) FROM students");
$totalStudents = $stmt->fetchColumn();

// Count today's attendance per status
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT status, COUNT(*) AS c FROM attendance WHERE attend_date = :d GROUP BY status");
$stmt->execute([':d' => $today]);

$todayCounts = ['Present' => 0, 'Absent' => 0, 'Late' => 0];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $todayCounts[$row['status']] = $row['c'];
}

$pageTitle = 'Dashboard';
include "partials/header.php";
?>

<h1>Dashboard</h1>

<!-- Stat cards -->
<div class="stats">
    <div class="stat-card"><div class="num"><?php echo $totalStudents; ?></div><div class="lbl">Total Students</div></div>
    <div class="stat-card"><div class="num"><?php echo $todayCounts['Present']; ?></div><div class="lbl">Present Today</div></div>
    <div class="stat-card"><div class="num"><?php echo $todayCounts['Absent']; ?></div><div class="lbl">Absent Today</div></div>
    <div class="stat-card"><div class="num"><?php echo $todayCounts['Late']; ?></div><div class="lbl">Late Today</div></div>
</div>

<!-- Quick actions -->
<div class="card">
    <h3>Quick actions</h3>
    <p>
        <a href="add_student.php" class="btn">Add Student</a>
        <a href="view_students.php" class="btn btn-secondary">View Students</a>
        <a href="attendance.php" class="btn btn-secondary">Mark Attendance</a>
        <a href="report.php" target="_blank" class="btn btn-secondary">PDF Report</a>
    </p>
    <p>
        <small class="text-muted">
            <strong>Grades / GPA:</strong> open <a href="view_students.php">Students</a> and click the green <em>Grades / GPA</em> button on any row.
        </small>
    </p>
</div>

<!-- Email status -->
<div class="card">
    <h3>System Status</h3>
    <p>
        <strong>Email notifications:</strong>
        <?php if ($emailReady): ?>
            <span class="badge badge-present">Active</span> — welcome emails are sent when you add a student.
        <?php else: ?>
            <span class="badge badge-late">Off</span> — to turn this on, edit the <code>mail</code> section in <code>includes/config.php</code>.
        <?php endif; ?>
    </p>
</div>

<?php include "partials/footer.php"; ?>
