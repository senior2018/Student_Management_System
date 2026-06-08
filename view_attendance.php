<?php
// View Attendance Records page (with date + student filters)

require "includes/auth.php";
require_login();
require "includes/db.php";

// Read filters from URL. Defaults: from = start of this month, to = today.
$from      = $_GET['from'] ?? date('Y-m-01');
$to        = $_GET['to']   ?? date('Y-m-d');
$studentId = (int) ($_GET['student_id'] ?? 0);

// Make sure dates look like YYYY-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');

// Build the SQL. JOIN gets the student's name.
$sql = "SELECT a.id, a.attend_date, a.status, s.fullname, s.course
        FROM attendance a
        JOIN students s ON s.id = a.student_id
        WHERE a.attend_date BETWEEN :from AND :to";
$params = [':from' => $from, ':to' => $to];

if ($studentId > 0) {
    $sql .= " AND s.id = :sid";
    $params[':sid'] = $studentId;
}
$sql .= " ORDER BY a.attend_date DESC, s.fullname ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load all students for the dropdown
$students = $pdo->query("SELECT id, fullname FROM students ORDER BY fullname")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Attendance Records';
include "partials/header.php";
?>

<h1>Attendance Records</h1>

<div class="card">
    <!-- Filter form -->
    <form method="get" action="view_attendance.php" class="searchbar">
        <label>From:</label>
        <input type="date" name="from" value="<?php echo e($from); ?>">

        <label>To:</label>
        <input type="date" name="to" value="<?php echo e($to); ?>">

        <select name="student_id">
            <option value="0">All students</option>
            <?php foreach ($students as $s): ?>
                <option value="<?php echo $s['id']; ?>" <?php if ($studentId == $s['id']) echo 'selected'; ?>>
                    <?php echo e($s['fullname']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn">Filter</button>
    </form>

    <?php if (empty($rows)): ?>
        <p>No attendance records in this range.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Date</th><th>Student</th><th>Course</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
            $cls = 'badge-' . strtolower($r['status']);
        ?>
            <tr>
                <td><?php echo e($r['attend_date']); ?></td>
                <td><?php echo e($r['fullname']); ?></td>
                <td><?php echo e($r['course']); ?></td>
                <td><span class="badge <?php echo $cls; ?>"><?php echo e($r['status']); ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include "partials/footer.php"; ?>
