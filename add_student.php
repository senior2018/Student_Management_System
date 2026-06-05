<?php
// Add Student page (the C in CRUD = Create).
// Shows a form, checks the input, saves the new student to the database.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/mail.php';

require_login();

// $errors = error messages keyed by field name
// $data   = form values (empty at first, refilled if there's an error)
$errors = [];
$data = ['fullname' => '', 'gender' => '', 'course' => '', 'email' => '', 'phone' => ''];

// Did the user submit the form?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check CSRF token first
    csrf_verify();

    // Validate the input. Returns errors + cleaned values.
    [$errors, $data] = validate_student($_POST);

    if (!$errors) {
        try {
            // If they uploaded a photo, handle that first
            $photo = handle_photo_upload($_FILES['photo'] ?? null);

            // Save to database (prepared statement = safe from SQL injection)
            $stmt = db()->prepare(
                'INSERT INTO students (fullname, gender, course, email, phone, photo)
                 VALUES (:fullname, :gender, :course, :email, :phone, :photo)'
            );
            $stmt->execute([
                ':fullname' => $data['fullname'],
                ':gender'   => $data['gender'],
                ':course'   => $data['course'],
                ':email'    => $data['email'],
                ':phone'    => $data['phone'],
                ':photo'    => $photo,
            ]);

            // Try to send a welcome email (don't fail the save if email breaks)
            try {
                send_welcome_email($data['email'], $data['fullname']);
            } catch (Throwable $mailErr) {
                error_log('Welcome email failed: ' . $mailErr->getMessage());
            }

            // Save a success message and redirect to the student list.
            // (POST → Redirect → GET prevents accidental double-save on refresh)
            flash_set('success', 'Student added successfully.');
            header('Location: view_students.php');
            exit;

        } catch (RuntimeException $e) {
            // Problem with the photo upload
            $errors['photo'] = $e->getMessage();
        } catch (PDOException $e) {
            // Database error. Error code 23000 means duplicate email.
            if ($e->getCode() === '23000') {
                $errors['email'] = 'A student with this email already exists.';
            } else {
                $errors['_db'] = 'Could not save the student. Please try again.';
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

    <!-- enctype is needed for file uploads -->
    <form method="post" action="add_student.php" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="fullname">Full Name</label>
            <!-- value="..." keeps the typed value if there's an error -->
            <input id="fullname" name="fullname" type="text" required value="<?= e($data['fullname']) ?>">
            <?php if (!empty($errors['fullname'])): ?><div class="error"><?= e($errors['fullname']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="">— Select —</option>
                <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                    <!-- "selected" keeps the chosen value highlighted -->
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

        <div class="form-group">
            <label for="photo">Photo (optional, JPG/PNG/GIF/WebP, max 2 MB)</label>
            <input id="photo" name="photo" type="file" accept="image/*">
            <?php if (!empty($errors['photo'])): ?><div class="error"><?= e($errors['photo']) ?></div><?php endif; ?>
        </div>

        <button class="btn" type="submit">Save Student</button>
        <a class="btn btn-secondary" href="view_students.php">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
