<?php
// Edit Student page (the U in CRUD = Update)

require "includes/auth.php";
require_login();
require "includes/db.php";
require "includes/helpers.php";

// Get the student id (from URL or form). (int) makes sure it's a number.
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid student ID.";
    header("Location: view_students.php");
    exit;
}

// Load the student
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
$stmt->execute([':id' => $id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    $_SESSION['error'] = "Student not found.";
    header("Location: view_students.php");
    exit;
}

// Fill the form with the current values
$fullname = $student['fullname'];
$gender   = $student['gender'];
$course   = $student['course'];
$email    = $student['email'];
$phone    = $student['phone'];
$photo    = $student['photo'];

$errors = [];

if (isset($_POST['save'])) {
    // Check CSRF token first
    csrf_check();

    $fullname = trim($_POST['fullname']);
    $gender   = $_POST['gender'];
    $course   = trim($_POST['course']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);

    // ---- Validate ----
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

    // ---- Handle new photo (if uploaded) ----
    if (!empty($_FILES['photo']['name'])) {
        if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            $errors['photo'] = "Photo must be 2 MB or smaller.";
        } else {
            // Check REAL file type with finfo (not what the browser says)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['photo']['tmp_name']);

            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];

            if (!isset($allowed[$mime])) {
                $errors['photo'] = "Photo must be JPG, PNG, GIF, or WebP.";
            } else {
                // Save new photo with unique name
                $ext = $allowed[$mime];
                $newPhoto = "student_" . time() . "_" . rand(1000, 9999) . "." . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/" . $newPhoto);

                // Delete the old photo (if any)
                if ($photo && file_exists("uploads/" . $photo)) {
                    unlink("uploads/" . $photo);
                }
                $photo = $newPhoto;
            }
        }
    }

    // ---- Update database ----
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "UPDATE students SET fullname=:fullname, gender=:gender, course=:course,
                 email=:email, phone=:phone, photo=:photo WHERE id=:id"
            );
            $stmt->execute([
                ':fullname' => $fullname,
                ':gender'   => $gender,
                ':course'   => $course,
                ':email'    => $email,
                ':phone'    => $phone,
                ':photo'    => $photo,
                ':id'       => $id,
            ]);

            $_SESSION['success'] = "Student updated successfully.";
            header("Location: view_students.php");
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors['email'] = "Another student is already using this email.";
            } else {
                $errors['_db'] = "Could not update the student.";
            }
        }
    }
}

$pageTitle = 'Edit Student';
include "partials/header.php";
?>

<h1>Edit Student #<?php echo $id; ?></h1>

<div class="card">
    <?php if (!empty($errors['_db'])): ?>
        <div class="alert alert-error"><?php echo e($errors['_db']); ?></div>
    <?php endif; ?>

    <form method="post" action="edit_student.php" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="fullname" value="<?php echo e($fullname); ?>" required>
            <?php if (!empty($errors['fullname'])): ?><div class="error"><?php echo e($errors['fullname']); ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label>Gender</label>
            <select name="gender" required>
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
            <label>Current photo</label>
            <?php if ($photo && file_exists("uploads/" . $photo)): ?>
                <img src="uploads/<?php echo e($photo); ?>" class="photo-preview">
            <?php else: ?>
                <p class="text-muted">No photo</p>
            <?php endif; ?>
            <label>Replace photo (optional)</label>
            <input type="file" name="photo" accept="image/*">
            <?php if (!empty($errors['photo'])): ?><div class="error"><?php echo e($errors['photo']); ?></div><?php endif; ?>
        </div>

        <button type="submit" name="save" class="btn">Save Changes</button>
        <a class="btn btn-secondary" href="view_students.php">Cancel</a>
    </form>
</div>

<?php include "partials/footer.php"; ?>
