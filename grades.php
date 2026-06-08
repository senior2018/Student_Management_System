<?php
// Grades page — manages grades for ONE student. URL needs ?student_id=N.

require "includes/auth.php";
require_login();
require "includes/db.php";
require "includes/helpers.php";

// Which student?
$studentId = (int) ($_GET['student_id'] ?? $_POST['student_id'] ?? 0);
if ($studentId <= 0) {
    $_SESSION['error'] = "Pick a student first.";
    header("Location: view_students.php");
    exit;
}

// Load the student
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
$stmt->execute([':id' => $studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    $_SESSION['error'] = "Student not found.";
    header("Location: view_students.php");
    exit;
}

$errors = [];

// ---- Add a grade ----
if (isset($_POST['add'])) {
    csrf_check();

    $subject = trim($_POST['subject']);
    $score   = $_POST['score'];
    $credits = (int) $_POST['credits'];

    if ($subject == "" || strlen($subject) > 100) {
        $errors['subject'] = "Subject is required.";
    }
    if (!is_numeric($score) || $score < 0 || $score > 100) {
        $errors['score'] = "Score must be a number between 0 and 100.";
    }
    if ($credits < 1 || $credits > 10) {
        $errors['credits'] = "Credits must be between 1 and 10.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO grades (student_id, subject, score, credits) VALUES (:sid, :sub, :sc, :cr)"
        );
        $stmt->execute([
            ':sid' => $studentId,
            ':sub' => $subject,
            ':sc'  => $score,
            ':cr'  => $credits,
        ]);
        $_SESSION['success'] = "Grade added.";
        header("Location: grades.php?student_id=$studentId");
        exit;
    }
}

// ---- Delete a grade ----
if (isset($_POST['delete'])) {
    csrf_check();

    $gradeId = (int) ($_POST['grade_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM grades WHERE id = :gid AND student_id = :sid");
    $stmt->execute([':gid' => $gradeId, ':sid' => $studentId]);
    $_SESSION['success'] = "Grade removed.";
    header("Location: grades.php?student_id=$studentId");
    exit;
}

// Load this student's grades
$stmt = $pdo->prepare("SELECT * FROM grades WHERE student_id = :sid ORDER BY created_at DESC");
$stmt->execute([':sid' => $studentId]);
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
$gpa = calculate_gpa($grades);

$pageTitle = 'Grades — ' . $student['fullname'];
include "partials/header.php";
?>

<h1>Grades for <?php echo e($student['fullname']); ?></h1>
<p>
    <a href="view_students.php" class="btn btn-secondary">← Back to students</a>
    <a href="edit_student.php?id=<?php echo $studentId; ?>" class="btn btn-secondary">Edit student</a>
</p>

<div class="card">
    <h3>
        Overall GPA: <?php echo $gpa === null ? '—' : number_format($gpa, 2); ?> / 5.00
        <?php if ($gpa !== null): ?>
            <small class="gpa-class">— <?php echo gpa_classification($gpa); ?></small>
        <?php endif; ?>
    </h3>
    <p><small class="text-muted">
        <strong>TCU 5.0-point scale:</strong> 70+ = A (5.0), 60+ = B+ (4.0), 50+ = B (3.0), 40+ = C (2.0), 35+ = D (1.0), below 35 = F (0.0).
        <br>GPA = sum(Grade Point × Credits) / sum(Credits).
    </small></p>
</div>

<!-- Add a new grade -->
<div class="card">
    <h3>Add a Grade</h3>
    <form method="post" action="grades.php?student_id=<?php echo $studentId; ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="student_id" value="<?php echo $studentId; ?>">

        <div class="form-group">
            <label>Subject</label>
            <input type="text" name="subject" required>
            <?php if (!empty($errors['subject'])): ?><div class="error"><?php echo e($errors['subject']); ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label>Score (0–100)</label>
            <input type="number" name="score" step="0.01" min="0" max="100" required>
            <?php if (!empty($errors['score'])): ?><div class="error"><?php echo e($errors['score']); ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label>Credits</label>
            <input type="number" name="credits" min="1" max="10" value="3" required>
            <?php if (!empty($errors['credits'])): ?><div class="error"><?php echo e($errors['credits']); ?></div><?php endif; ?>
        </div>

        <button type="submit" name="add" class="btn">Add Grade</button>
    </form>
</div>

<!-- List of grades -->
<div class="card">
    <h3>Grades</h3>
    <?php if (empty($grades)): ?>
        <p>No grades yet — add the first one above.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Subject</th><th>Score</th><th>Credits</th><th>Letter</th><th>Grade Point</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($grades as $g): ?>
                <tr>
                    <td><?php echo e($g['subject']); ?></td>
                    <td><?php echo number_format($g['score'], 2); ?></td>
                    <td><?php echo $g['credits']; ?></td>
                    <td><strong><?php echo score_to_letter($g['score']); ?></strong></td>
                    <td><?php echo number_format(score_to_gpa($g['score']), 1); ?></td>
                    <td>
                        <!-- Delete uses a POST form so we can include a CSRF token -->
                        <form method="post" action="grades.php?student_id=<?php echo $studentId; ?>" class="inline-form" onsubmit="return confirm('Remove this grade?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="student_id" value="<?php echo $studentId; ?>">
                            <input type="hidden" name="grade_id" value="<?php echo $g['id']; ?>">
                            <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include "partials/footer.php"; ?>
