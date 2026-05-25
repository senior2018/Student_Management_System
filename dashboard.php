<?php
// ====================================================================
// DASHBOARD
// The first page admins see after logging in. Shows summary stats
// (total students, today's attendance counts) and quick-action links.
// ====================================================================
 
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Block this page if the user isn't logged in
require_login();
 
// ---- Count all students ----
// query() runs a simple SELECT immediately and returns the result.
// fetchColumn() grabs the first column of the first row (the COUNT(*)).
// We cast to int with (int) to be sure we're working with a number.
$totalStudents = (int) db()->query('SELECT COUNT(*) FROM students')->fetchColumn();

// ---- Count today's attendance by status ----
$today = date('Y-m-d');   // current date in "YYYY-MM-DD" format

// Prepared statement with a placeholder :d for safe SQL
$stmt = db()->prepare('SELECT status, COUNT(*) AS c FROM attendance WHERE attend_date = :d GROUP BY status');
$stmt->execute([':d' => $today]);

// Start with zeros for each possible status
$todayCounts = ['Present' => 0, 'Absent' => 0, 'Late' => 0];

// Fill in whatever counts the DB gave us
foreach ($stmt->fetchAll() as $row) {
    $todayCounts[$row['status']] = (int) $row['c'];
}

$pageTitle = 'Dashboard';

// Pull in the shared top nav / opening HTML
include __DIR__ . '/partials/header.php';
?>

<h1>Dashboard</h1>

<!-- Stat cards: one per metric -->
<div class="stats">
    <div class="stat-card"><div class="num"><?= $totalStudents ?></div><div class="lbl">Total Students</div></div>
    <div class="stat-card"><div class="num"><?= $todayCounts['Present'] ?></div><div class="lbl">Present Today</div></div>
    <div class="stat-card"><div class="num"><?= $todayCounts['Absent'] ?></div><div class="lbl">Absent Today</div></div>
    <div class="stat-card"><div class="num"><?= $todayCounts['Late'] ?></div><div class="lbl">Late Today</div></div>
</div>

<!-- Quick action buttons to common pages -->
<div class="card">
    <h3>Quick actions</h3>
    <p>
        <a href="add_student.php" class="btn">Add Student</a>
        <a href="view_students.php" class="btn btn-secondary">View Students</a>
        <a href="attendance.php" class="btn btn-secondary">Mark Attendance</a>
    </p>
</div>

<?php
// Closing HTML (footer + </body></html>)
include __DIR__ . '/partials/footer.php';
?>
