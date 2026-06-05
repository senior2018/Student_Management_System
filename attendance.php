<?php
// Mark Attendance page.
// 1. GET → shows every student with radio buttons (Present / Absent / Late)
// 2. POST → saves the chosen status for each student on the selected date

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

// Get the date from the URL or form. Default = today.
$date = $_GET['date'] ?? $_POST['date'] ?? date('Y-m-d');

// Make sure the date is YYYY-MM-DD format. If not, use today.
// (without this, someone could send a weird string into the SQL query)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

// Did they submit the form?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // The radio buttons are named status[STUDENT_ID], so $_POST['status']
    // is an array like [12 => 'Present', 13 => 'Absent', ...]
    $statuses = $_POST['status'] ?? [];
    if (!is_array($statuses)) {
        $statuses = [];
    }

    // The only allowed status values
    $valid = ['Present', 'Absent', 'Late'];

    // INSERT ... ON DUPLICATE KEY UPDATE means:
    //   - if no row exists for this (student, date), add a new one
    //   - if one already exists, update the status
    // So you can safely change today's attendance many times.
    $upsert = db()->prepare(
        'INSERT INTO attendance (student_id, attend_date, status)
         VALUES (:sid, :d, :s)
         ON DUPLICATE KEY UPDATE status = VALUES(status)'
    );

    $saved = 0;
    foreach ($statuses as $sid => $status) {
        $sid = (int) $sid;

        // Skip anything weird
        if ($sid <= 0 || !in_array($status, $valid, true)) {
            continue;
        }
        $upsert->execute([':sid' => $sid, ':d' => $date, ':s' => $status]);
        $saved++;
    }

    flash_set('success', "Attendance saved for {$saved} student(s) on {$date}.");

    // Redirect back so refresh doesn't re-submit the form
    header('Location: attendance.php?date=' . urlencode($date));
    exit;
}

// Load all students (sorted by name)
$students = db()->query('SELECT id, fullname, course FROM students ORDER BY fullname')->fetchAll();

// Load existing attendance for this date so we can pre-select the radios
$stmt = db()->prepare('SELECT student_id, status FROM attendance WHERE attend_date = :d');
$stmt->execute([':d' => $date]);

// Turn the rows into a map: studentId → status
$current = [];
foreach ($stmt->fetchAll() as $row) {
    $current[(int) $row['student_id']] = $row['status'];
}

$pageTitle = 'Mark Attendance';
include __DIR__ . '/partials/header.php';
?>

<h1>Mark Attendance</h1>

<div class="card">
    <!-- Date picker (uses GET — date shows up in the URL) -->
    <form method="get" action="attendance.php" class="searchbar">
        <label for="date" style="align-self:center;">Date:</label>
        <input type="date" id="date" name="date" value="<?= e($date) ?>" max="<?= date('Y-m-d') ?>">
        <button class="btn" type="submit">Load</button>
    </form>

    <?php if (!$students): ?>
        <p>No students yet. <a href="add_student.php">Add one</a> first.</p>
    <?php else: ?>
    <!-- Save form (POST because it changes data) -->
    <form method="post" action="attendance.php">
        <?= csrf_field() ?>
        <input type="hidden" name="date" value="<?= e($date) ?>">

        <table>
            <thead>
                <tr><th>ID</th><th>Name</th><th>Course</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($students as $s):
                $sid = (int) $s['id'];
                // Current status for this student on this date (or empty)
                $cur = $current[$sid] ?? '';
            ?>
                <tr>
                    <td><?= $sid ?></td>
                    <td><?= e($s['fullname']) ?></td>
                    <td><?= e($s['course']) ?></td>
                    <td>
                        <?php foreach (['Present', 'Absent', 'Late'] as $opt): ?>
                            <label style="margin-right:10px;">
                                <!-- name="status[12]" makes PHP receive these as an array -->
                                <input type="radio" name="status[<?= $sid ?>]" value="<?= $opt ?>" <?= $cur === $opt ? 'checked' : '' ?>>
                                <?= $opt ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p style="margin-top:16px;">
            <button class="btn" type="submit">Save Attendance</button>
        </p>
    </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
