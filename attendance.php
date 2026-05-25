<?php
// ====================================================================
// MARK ATTENDANCE
// 1. GET → show every student with radio buttons (Present/Absent/Late)
//          for the chosen date. Any saved status is pre-selected.
// 2. POST → save (or update) one attendance row per student for that date.
// ====================================================================

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

// Read the date from the URL (?date=…) or POST. Default = today.
$date = $_GET['date'] ?? $_POST['date'] ?? date('Y-m-d');

// Make sure the date is in YYYY-MM-DD format. If not, fall back to today.
// Without this an attacker could send a weird string into the SQL query.
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

// ---- Handle form submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // The radio buttons are named status[STUDENT_ID], so $_POST['status']
    // arrives as an array like:  [12 => 'Present', 13 => 'Absent', ...]
    $statuses = $_POST['status'] ?? [];
    if (!is_array($statuses)) {
        $statuses = [];
    }

    // The only acceptable status values
    $valid = ['Present', 'Absent', 'Late'];

    // INSERT … ON DUPLICATE KEY UPDATE
    //   If no row exists for (student_id, date), INSERT a new row.
    //   If one already exists (the UNIQUE key blocks duplicates), UPDATE its status.
    // This makes "mark today's attendance" safe to run multiple times.
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

    // urlencode() makes sure the date in the URL is safe (e.g. converts spaces)
    header('Location: attendance.php?date=' . urlencode($date));
    exit;
}

// ---- Load data for the form ----

// Every student, alphabetically
$students = db()->query('SELECT id, fullname, course FROM students ORDER BY fullname')->fetchAll();

// Existing attendance for this date so we can pre-select the radios
$stmt = db()->prepare('SELECT student_id, status FROM attendance WHERE attend_date = :d');
$stmt->execute([':d' => $date]);

// Turn the rows into a map:  studentId → 'Present'/'Absent'/'Late'
$current = [];
foreach ($stmt->fetchAll() as $row) {
    $current[(int) $row['student_id']] = $row['status'];
}

$pageTitle = 'Mark Attendance';
include __DIR__ . '/partials/header.php';
?>

<h1>Mark Attendance</h1>

<div class="card">
    <!-- Date picker (GET form — date appears in the URL) -->
    <form method="get" action="attendance.php" class="searchbar">
        <label for="date" style="align-self:center;">Date:</label>
        <input type="date" id="date" name="date" value="<?= e($date) ?>" max="<?= date('Y-m-d') ?>">
        <button class="btn" type="submit">Load</button>
    </form>

    <?php if (!$students): ?>
        <p>No students yet. <a href="add_student.php">Add one</a> first.</p>
    <?php else: ?>
    <!-- Attendance form (POST — secret + makes changes) -->
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
                // Existing status for this student on this date, or '' if none
                $cur = $current[$sid] ?? '';
            ?>
                <tr>
                    <td><?= $sid ?></td>
                    <td><?= e($s['fullname']) ?></td>
                    <td><?= e($s['course']) ?></td>
                    <td>
                        <?php foreach (['Present', 'Absent', 'Late'] as $opt): ?>
                            <label style="margin-right:10px;">
                                <!--
                                    name="status[12]" makes PHP receive these as an array.
                                    "checked" pre-selects the saved choice.
                                -->
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
