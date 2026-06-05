<?php
// Dashboard — the first page admins see after logging in.
// Shows quick stats and links to common actions.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/mail.php';

// Only logged-in users can see this page
require_login();

// Is email turned on? (null means not set up yet)
$emailReady = mail_config() !== null;

// Count all students
$totalStudents = (int) db()->query('SELECT COUNT(*) FROM students')->fetchColumn();

// Count today's attendance per status
$today = date('Y-m-d');

$stmt = db()->prepare('SELECT status, COUNT(*) AS c FROM attendance WHERE attend_date = :d GROUP BY status');
$stmt->execute([':d' => $today]);

// Start each status at 0
$todayCounts = ['Present' => 0, 'Absent' => 0, 'Late' => 0];

// Fill in whatever counts the DB gave us
foreach ($stmt->fetchAll() as $row) {
    $todayCounts[$row['status']] = (int) $row['c'];
}

$pageTitle = 'Dashboard';

// Load the shared header (nav bar, etc.)
include __DIR__ . '/partials/header.php';
?>

<h1>Dashboard</h1>

<!-- One stat card per number we care about -->
<div class="stats">
    <div class="stat-card"><div class="num"><?= $totalStudents ?></div><div class="lbl">Total Students</div></div>
    <div class="stat-card"><div class="num"><?= $todayCounts['Present'] ?></div><div class="lbl">Present Today</div></div>
    <div class="stat-card"><div class="num"><?= $todayCounts['Absent'] ?></div><div class="lbl">Absent Today</div></div>
    <div class="stat-card"><div class="num"><?= $todayCounts['Late'] ?></div><div class="lbl">Late Today</div></div>
</div>

<!-- Buttons to common pages -->
<div class="card">
    <h3>Quick actions</h3>
    <p>
        <a href="add_student.php" class="btn">Add Student</a>
        <a href="view_students.php" class="btn btn-secondary">View Students</a>
        <a href="attendance.php" class="btn btn-secondary">Mark Attendance</a>
        <a href="report.php" target="_blank" class="btn btn-secondary">PDF Report</a>
    </p>
    <p>
        <small style="color:#64748b;">
            <strong>Grades / GPA:</strong> open <a href="view_students.php">Students</a> and click the green <em>Grades / GPA</em> button on any row.
        </small>
    </p>
</div>

<!-- Shows whether email is set up or not -->
<div class="card">
    <h3>System Status</h3>
    <p>
        <strong>Email notifications:</strong>
        <?php if ($emailReady): ?>
            <span class="badge badge-present">Active</span> — welcome emails are sent when you add a student.
        <?php else: ?>
            <span class="badge badge-late">Off</span> — to turn this on, edit the <code>mail</code> section in <code>includes/config.php</code> (Gmail app password). The app still works without it.
        <?php endif; ?>
    </p>
</div>

<?php
// Load the shared footer
include __DIR__ . '/partials/footer.php';
?>
