<?php
// ====================================================================
// ADD STUDENT (the "C" in CRUD — Create)
// Shows a form, validates the input, and inserts a new student row.
// ====================================================================
 
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Only logged-in admins can add students
require_login();

// $errors  = field-by-field error messages
// $data    = the form values (empty on first load, re-filled if validation fails)
$errors = [];
$data = ['fullname' => '', 'gender' => '', 'course' => '', 'email' => '', 'phone' => ''];

// Did the user submit the form?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify the CSRF token first
    csrf_verify();

    // validate_student() returns [errors, cleaned-data]
    [$errors, $data] = validate_student($_POST);

    if (!$errors) {
        try {
            // Prepared INSERT with named placeholders. Safe from SQL injection.
            $stmt = db()->prepare(
                'INSERT INTO students (fullname, gender, course, email, phone)
                 VALUES (:fullname, :gender, :course, :email, :phone)'
            );
            $stmt->execute([
                ':fullname' => $data['fullname'],
                ':gender'   => $data['gender'],
                ':course'   => $data['course'],
                ':email'    => $data['email'],
                ':phone'    => $data['phone'],
            ]);

            // Set a one-time message for the next page, then redirect.
            // PRG pattern (Post → Redirect → Get) prevents accidental
            // double submission when the user hits refresh.
            flash_set('success', 'Student added successfully.');
            header('Location: view_students.php');
            exit;

        } catch (PDOException $e) {
            // SQL error code 23000 = "integrity constraint violation"
            // Usually means a UNIQUE column already has this value.
            if ($e->getCode() === '23000') {
                $errors['email'] = 'A student with this email already exists.';
            } else {
                $errors['_db'] = 'Could not save student. Please try again.';
            }
        }
    }
}

$pageTitle = 'Add Student';
include __DIR__ . '/partials/header.php';
?>

<h1>Add Student</h1>

<div class="card">
    <?php if (!empty($errors['_db'])): ?>
        <div class="alert alert-error"><?= e($errors['_db']) ?></div>
    <?php endif; ?>

    <form method="post" action="add_student.php">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="fullname">Full Name</label>
            <!-- value="…" keeps what the user typed, so they don't lose it on error -->
            <input id="fullname" name="fullname" type="text" required value="<?= e($data['fullname']) ?>">
            <?php if (!empty($errors['fullname'])): ?><div class="error"><?= e($errors['fullname']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="">— Select —</option>
                <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                    <!-- "selected" keeps the previously chosen value highlighted -->
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

        <button class="btn" type="submit">Save Student</button>
        <a class="btn btn-secondary" href="view_students.php">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
