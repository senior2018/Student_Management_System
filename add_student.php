<?php
// Add Student page (the C in CRUD = Create)

require "includes/auth.php";
require_login();
require "includes/db.php";
require "includes/helpers.php";
require "includes/mail.php";

$errors = [];
$fullname = $gender = $course = $email = $phone = "";

if (isset($_POST['save'])) {
    // Check CSRF token first
    csrf_check();

    // Read the form values
    $fullname = trim($_POST['fullname']);
    $gender   = $_POST['gender'];
    $course   = trim($_POST['course']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);

    // ---- Validate the form ----
    if ($fullname == "" || strlen($fullname) > 100) {
        $errors['fullname'] = "Full name is required (max 100 characters).";
    }
    if (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors['gender'] = "Please pick a gender.";
    }
    if ($course == "" || strlen($course) > 100) {
        $errors['course'] = "Course is required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email.";
    }
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $errors['phone'] = "Phone must be 7-20 characters (numbers, spaces, + - () allowed).";
    }

    // ---- Handle the photo (if uploaded) ----
    $photo = null;
    if (!empty($_FILES['photo']['name'])) {
        if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            $errors['photo'] = "Photo must be 2 MB or smaller.";
        } else {
            // Check the REAL file type with finfo (don't trust the browser)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['photo']['tmp_name']);

            // Allowed MIME types → file extension
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];

            if (!isset($allowed[$mime])) {
                $errors['photo'] = "Photo must be JPG, PNG, GIF, or WebP.";
            } else {
                // Save with a unique filename so files don't overwrite each other
                $ext = $allowed[$mime];
                $photo = "student_" . time() . "_" . rand(1000, 9999) . "." . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/" . $photo);
            }
        }
    }

    // ---- Save to database ----
    if (empty($errors)) {
        try {
            // Prepared statement = safe from SQL injection
            $stmt = $pdo->prepare(
                "INSERT INTO students (fullname, gender, course, email, phone, photo)
                 VALUES (:fullname, :gender, :course, :email, :phone, :photo)"
            );
            $stmt->execute([
                ':fullname' => $fullname,
                ':gender'   => $gender,
                ':course'   => $course,
                ':email'    => $email,
                ':phone'    => $phone,
                ':photo'    => $photo,
            ]);

            // Try to send a welcome email (skip silently if it fails)
            try {
                send_welcome_email($email, $fullname);
            } catch (Exception $e) {
                // ignore — email is optional
            }

            $_SESSION['success'] = "Student added successfully.";
            header("Location: view_students.php");
            exit;

        } catch (PDOException $e) {
            // 23000 = duplicate (the email already exists)
            if ($e->getCode() == 23000) {
                $errors['email'] = "A student with this email already exists.";
            } else {
                $errors['_db'] = "Could not save the student.";
            }
        }
    }
}

$pageTitle = 'Add Student';
include "partials/header.php";
?>

<h1>Add Student</h1>

<div class="card">
    <?php if (!empty($errors['_db'])): ?>
        <div class="alert alert-error"><?php echo e($errors['_db']); ?></div>
    <?php endif; ?>

    <!-- enctype is needed for file uploads -->
    <form method="post" action="add_student.php" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="fullname" value="<?php echo e($fullname); ?>" required>
            <?php if (!empty($errors['fullname'])): ?><div class="error"><?php echo e($errors['fullname']); ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label>Gender</label>
            <select name="gender" required>
                <option value="">— Select —</option>
                <option value="Male"   <?php if ($gender == 'Male')   echo 'selected'; ?>>Male</option>
                <option value="Female" <?php if ($gender == 'Female') echo 'selected'; ?>>Female</option>
                <option value="Other"  <?php if ($gender == 'Other')  echo 'selected'; ?>>Other</option>
            </select>
            <?php if (!empty($errors['gender'])): ?><div class="error"><?php echo e($errors['gender']); ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label>Course</label>
            <input type="text" name="course" value="<?php echo e($course); ?>" required>
            <?php if (!empty($errors['course'])): ?><div class="error"><?php echo e($errors['course']); ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo e($email); ?>" required>
            <?php if (!empty($errors['email'])): ?><div class="error"><?php echo e($errors['email']); ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?php echo e($phone); ?>" required>
            <?php if (!empty($errors['phone'])): ?><div class="error"><?php echo e($errors['phone']); ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label>Photo (optional, JPG/PNG/GIF/WebP, max 2 MB)</label>
            <input type="file" name="photo" accept="image/*">
            <?php if (!empty($errors['photo'])): ?><div class="error"><?php echo e($errors['photo']); ?></div><?php endif; ?>
        </div>

        <button type="submit" name="save" class="btn">Save Student</button>
        <a class="btn btn-secondary" href="view_students.php">Cancel</a>
    </form>
</div>

<?php include "partials/footer.php"; ?>
