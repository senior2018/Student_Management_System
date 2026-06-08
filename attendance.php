<?php
// Mark Attendance page

require "includes/auth.php";
require_login();
require "includes/db.php";

// Get the date (default = today)
$date = $_GET['date'] ?? $_POST['date'] ?? date('Y-m-d');

// Make sure the date is YYYY-MM-DD format. If not, use today.
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

// Did they submit attendance?
if (isset($_POST['save'])) {
    // Check CSRF token first
    csrf_check();

    $statuses = $_POST['status'] ?? [];
    $valid = ['Present', 'Absent', 'Late'];

    // ON DUPLICATE KEY UPDATE: if a record exists for this student+date,
    // update the status. Otherwise insert a new one.
    $stmt = $pdo->prepare(
        "INSERT INTO attendance (student_id, attend_date, status)
         VALUES (:sid, :d, :s)
         ON DUPLICATE KEY UPDATE status = VALUES(status)"
    );

    $saved = 0;
    foreach ($statuses as $sid => $status) {
        $sid = (int) $sid;
        if ($sid > 0 && in_array($status, $valid)) {
            $stmt->execute([':sid' => $sid, ':d' => $date, ':s' => $status]);
            $saved++;
        }
    }

    $_SESSION['success'] = "Attendance saved for $saved student(s) on $date.";
    header("Location: attendance.php?date=" . urlencode($date));
    exit;
}

// Load all students
$students = $pdo->query("SELECT id, fullname, course FROM students ORDER BY fullname")->fetchAll(PDO::FETCH_ASSOC);

// Load existing attendance for this date
$stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE attend_date = :d");
$stmt->execute([':d' => $date]);

// Build a map: studentId → status
$current = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $current[$row['student_id']] = $row['status'];
}

$pageTitle = 'Mark Attendance';
include "partials/header.php";
?>

<h1>Mark Attendance</h1>

<div class="card">
    <!-- Date picker -->
    <form method="get" action="attendance.php" class="searchbar">
        <label>Date:</label>
        <input type="date" name="date" value="<?php echo e($date); ?>" max="<?php echo date('Y-m-d'); ?>">
        <button type="submit" class="btn">Load</button>
    </form>

    <?php if (empty($students)): ?>
        <p>No students yet. <a href="add_student.php">Add one</a> first.</p>
    <?php else: ?>
    <form method="post" action="attendance.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="date" value="<?php echo e($date); ?>">

        <table>
            <thead>
                <tr><th>ID</th><th>Name</th><th>Course</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($students as $s):
                $sid = $s['id'];
                $cur = $current[$sid] ?? '';
            ?>
                <tr>
                    <td><?php echo $sid; ?></td>
                    <td><?php echo e($s['fullname']); ?></td>
                    <td><?php echo e($s['course']); ?></td>
                    <td>
                        <?php foreach (['Present', 'Absent', 'Late'] as $opt): ?>
                            <label class="radio-option">
                                <input type="radio" name="status[<?php echo $sid; ?>]" value="<?php echo $opt; ?>" <?php if ($cur == $opt) echo 'checked'; ?>>
                                <?php echo $opt; ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p class="submit-row">
            <button type="submit" name="save" class="btn">Save Attendance</button>
        </p>
    </form>
    <?php endif; ?>
</div>

<?php include "partials/footer.php"; ?>
