<?php
// Grades page.
// Manages the grades for ONE student. The URL needs ?student_id=N.
// Admin can add a grade (subject + score + credits) and delete grades.
// Shows the student's GPA at the top.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

// Which student are we managing? (int) for safety.
$studentId = (int) ($_GET['student_id'] ?? $_POST['student_id'] ?? 0);
if ($studentId <= 0) {
    flash_set('error', 'Pick a student first.');
    header('Location: view_students.php');
    exit;
}

// Load the student (so we can show their name + confirm they exist)
$s = db()->prepare('SELECT * FROM students WHERE id = :id');
$s->execute([':id' => $studentId]);
$student = $s->fetch();
if (!$student) {
    flash_set('error', 'Student not found.');
    header('Location: view_students.php');
    exit;
}

$errors = [];

// Did they submit the form?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    // ---- ADD a grade ----
    if ($action === 'add') {
        $subject = trim($_POST['subject'] ?? '');
        $score   = $_POST['score'] ?? '';
        $credits = (int) ($_POST['credits'] ?? 3);

        // Validate the inputs
        if ($subject === '' || strlen($subject) > 100) {
            $errors['subject'] = 'Subject is required (max 100 chars).';
        }
        if (!is_numeric($score) || (float) $score < 0 || (float) $score > 100) {
            $errors['score'] = 'Score must be a number between 0 and 100.';
        }
        if ($credits < 1 || $credits > 10) {
            $errors['credits'] = 'Credits must be between 1 and 10.';
        }

        if (!$errors) {
            $ins = db()->prepare(
                'INSERT INTO grades (student_id, subject, score, credits) VALUES (:sid, :sub, :sc, :cr)'
            );
            $ins->execute([
                ':sid' => $studentId,
                ':sub' => $subject,
                ':sc'  => (float) $score,
                ':cr'  => $credits,
            ]);
            flash_set('success', 'Grade added.');
            header('Location: grades.php?student_id=' . $studentId);
            exit;
        }
    }

    // ---- DELETE a grade ----
    if ($action === 'delete') {
        $gradeId = (int) ($_POST['grade_id'] ?? 0);
        // Check the grade actually belongs to this student before deleting
        $del = db()->prepare('DELETE FROM grades WHERE id = :gid AND student_id = :sid');
        $del->execute([':gid' => $gradeId, ':sid' => $studentId]);
        flash_set('success', 'Grade removed.');
        header('Location: grades.php?student_id=' . $studentId);
        exit;
    }
}

// Load all grades for this student (newest first)
$gr = db()->prepare('SELECT * FROM grades WHERE student_id = :sid ORDER BY created_at DESC');
$gr->execute([':sid' => $studentId]);
$grades = $gr->fetchAll();
$gpa = calculate_gpa($grades);

$pageTitle = 'Grades — ' . $student['fullname'];
include __DIR__ . '/partials/header.php';
?>

<h1>Grades for <?= e($student['fullname']) ?></h1>
<p>
    <a href="view_students.php" class="btn btn-secondary">← Back to students</a>
    <a href="edit_student.php?id=<?= (int) $studentId ?>" class="btn btn-secondary">Edit student</a>
</p>

<div class="card">
    <h3>
        Overall GPA: <?= $gpa === null ? '—' : number_format($gpa, 2) ?> / 5.00
        <?php if ($gpa !== null): ?>
            <small style="color:#64748b;font-weight:normal;">— <?= e(gpa_classification($gpa)) ?></small>
        <?php endif; ?>
    </h3>
    <p><small style="color:#64748b;">
        <strong>TCU 5.0-point scale</strong> (Tanzania): 70+ = A (5.0), 60+ = B+ (4.0), 50+ = B (3.0), 40+ = C (2.0), 35+ = D (1.0), below 35 = F (0.0). Pass mark is 50%.
        <br>GPA = sum(Grade Point × Credits) / sum(Credits).
    </small></p>
</div>

<!-- Add a new grade -->
<div class="card">
    <h3>Add a Grade</h3>
    <form method="post" action="grades.php?student_id=<?= (int) $studentId ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="student_id" value="<?= (int) $studentId ?>">

        <div class="form-group">
            <label for="subject">Subject</label>
            <input id="subject" name="subject" type="text" required>
            <?php if (!empty($errors['subject'])): ?><div class="error"><?= e($errors['subject']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="score">Score (0–100)</label>
            <!-- step="0.01" lets you type decimals like 85.5 -->
            <input id="score" name="score" type="number" step="0.01" min="0" max="100" required>
            <?php if (!empty($errors['score'])): ?><div class="error"><?= e($errors['score']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="credits">Credits</label>
            <input id="credits" name="credits" type="number" min="1" max="10" value="3" required>
            <?php if (!empty($errors['credits'])): ?><div class="error"><?= e($errors['credits']) ?></div><?php endif; ?>
        </div>

        <button class="btn" type="submit">Add Grade</button>
    </form>
</div>

<!-- List the existing grades -->
<div class="card">
    <h3>Grades</h3>
    <?php if (!$grades): ?>
        <p>No grades yet — add the first one above.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Subject</th><th>Score</th><th>Credits</th><th>Letter</th><th>Grade Point</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($grades as $g): ?>
                <tr>
                    <td><?= e($g['subject']) ?></td>
                    <td><?= number_format((float) $g['score'], 2) ?></td>
                    <td><?= (int) $g['credits'] ?></td>
                    <td><strong><?= e(score_to_letter((float) $g['score'])) ?></strong></td>
                    <td><?= number_format(score_to_gpa((float) $g['score']), 1) ?></td>
                    <td>
                        <form method="post" action="grades.php?student_id=<?= (int) $studentId ?>" onsubmit="return confirm('Remove this grade?');" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="student_id" value="<?= (int) $studentId ?>">
                            <input type="hidden" name="grade_id" value="<?= (int) $g['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
