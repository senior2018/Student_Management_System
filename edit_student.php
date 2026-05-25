<?php
// ====================================================================
// EDIT STUDENT (the "U" in CRUD — Update)
// 1. GET with ?id=N → load the student and show the form prefilled
// 2. POST → validate the new values and UPDATE the row
// ====================================================================
 
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

// Pull the id either from the URL (?id=…) on GET, or from the form on POST.
// We cast to int — that alone makes SQL injection through the id impossible.
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    flash_set('error', 'Invalid student ID.');
    header('Location: view_students.php');
    exit;
}
 
// Load the existing student. If not found, bail back to the list.
$stmt = db()->prepare('SELECT * FROM students WHERE id = :id');
$stmt->execute([':id' => $id]);
$student = $stmt->fetch();

if (!$student) {
    flash_set('error', 'Student not found.');
    header('Location: view_students.php');
    exit;
}

// Pre-fill the form with the student's current values.
$errors = [];
$data = [
    'fullname' => $student['fullname'],
    'gender'   => $student['gender'],
    'course'   => $student['course'],
    'email'    => $student['email'],
    'phone'    => $student['phone'],
];

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Validate the submitted values; overwrite $data with the cleaned versions
    [$errors, $data] = validate_student($_POST);

    if (!$errors) {
        try {
            // UPDATE the row identified by :id
            $stmt = db()->prepare(
                'UPDATE students SET fullname=:fullname, gender=:gender, course=:course,
                 email=:email, phone=:phone WHERE id=:id'
            );
            $stmt->execute([
                ':fullname' => $data['fullname'],
                ':gender'   => $data['gender'],
                ':course'   => $data['course'],
                ':email'    => $data['email'],
                ':phone'    => $data['phone'],
                ':id'       => $id,
            ]);

            flash_set('success', 'Student updated successfully.');
            header('Location: view_students.php');
            exit;

        } catch (PDOException $e) {
            // 23000 = duplicate email (UNIQUE constraint hit)
            if ($e->getCode() === '23000') {
                $errors['email'] = 'Another student is already using this email.';
            } else {
                $errors['_db'] = 'Could not update student. Please try again.';
            }
        }
    }
}

$pageTitle = 'Edit Student';
include __DIR__ . '/partials/header.php';
?>

<h1>Edit Student #<?= (int) $id ?></h1>

<div class="card">
    <?php if (!empty($errors['_db'])): ?>
        <div class="alert alert-error"><?= e($errors['_db']) ?></div>
    <?php endif; ?>

    <form method="post" action="edit_student.php">
        <?= csrf_field() ?>
        <!-- Pass the id back so the POST handler knows which row to update -->
        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <div class="form-group">
            <label for="fullname">Full Name</label>
            <input id="fullname" name="fullname" type="text" required value="<?= e($data['fullname']) ?>">
            <?php if (!empty($errors['fullname'])): ?><div class="error"><?= e($errors['fullname']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                    <option value="<?= $g ?>" <?= $data['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['gender'])): ?><div class="error"><?= e($errors['gender']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="course">Course</label>
            <input id="course" name="course" type="text" required value="<?= e($data['course']) ?>">
            <?php if (!empty($errors['course'])): ?><div class="error"><?= e($errors['course']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required value="<?= e($data['email']) ?>">
            <?php if (!empty($errors['email'])): ?><div class="error"><?= e($errors['email']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input id="phone" name="phone" type="text" required value="<?= e($data['phone']) ?>">
            <?php if (!empty($errors['phone'])): ?><div class="error"><?= e($errors['phone']) ?></div><?php endif; ?>
        </div>

        <button class="btn" type="submit">Save Changes</button>
        <a class="btn btn-secondary" href="view_students.php">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
