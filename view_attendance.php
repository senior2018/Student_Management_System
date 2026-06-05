<?php
// View Attendance Records page.
// Lets you browse past attendance with three filters:
//   from date, to date, and one specific student (optional).
// Defaults: from = first day of this month, to = today.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

// Read the filters from the URL. Use sensible defaults if missing.
$from = $_GET['from'] ?? date('Y-m-01');    // first day of this month
$to   = $_GET['to']   ?? date('Y-m-d');     // today
$studentId = (int) ($_GET['student_id'] ?? 0);   // 0 = all students

// Make sure the dates look like YYYY-MM-DD. If not, use today.
foreach (['from', 'to'] as $k) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $$k)) {
        $$k = date('Y-m-d');
    }
}

// Build the SQL in pieces so we can add the student filter only if needed.
// JOIN gets the student's name from the students table.
$sql = 'SELECT a.id, a.attend_date, a.status, s.id AS student_id, s.fullname, s.course
        FROM attendance a
        JOIN students s ON s.id = a.student_id
        WHERE a.attend_date BETWEEN :from AND :to';
$params = [':from' => $from, ':to' => $to];

// Add student filter only if one was picked
if ($studentId > 0) {
    $sql .= ' AND s.id = :sid';
    $params[':sid'] = $studentId;
}
$sql .= ' ORDER BY a.attend_date DESC, s.fullname ASC';

// Run the query (prepared = safe)
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Also load all students for the dropdown filter
$students = db()->query('SELECT id, fullname FROM students ORDER BY fullname')->fetchAll();

$pageTitle = 'Attendance Records';
include __DIR__ . '/partials/header.php';
?>

<h1>Attendance Records</h1>

<div class="card">
    <!-- Filter form (GET so the filters show up in the URL) -->
    <form method="get" action="view_attendance.php" class="searchbar">
        <label style="align-self:center;">From:</label>
        <input type="date" name="from" value="<?= e($from) ?>">

        <label style="align-self:center;">To:</label>
        <input type="date" name="to" value="<?= e($to) ?>">

        <select name="student_id">
            <option value="0">All students</option>
            <?php foreach ($students as $s): ?>
                <option value="<?= (int) $s['id'] ?>" <?= $studentId === (int) $s['id'] ? 'selected' : '' ?>>
                    <?= e($s['fullname']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="btn" type="submit">Filter</button>
    </form>

    <?php if (!$rows): ?>
        <p>No attendance records in this range.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Date</th><th>Student</th><th>Course</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
            // Build CSS class name like badge-present / badge-absent / badge-late
            $cls = 'badge-' . strtolower($r['status']);
        ?>
            <tr>
                <td><?= e($r['attend_date']) ?></td>
                <td><?= e($r['fullname']) ?></td>
                <td><?= e($r['course']) ?></td>
                <td><span class="badge <?= $cls ?>"><?= e($r['status']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
