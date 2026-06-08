<?php
// Delete Student (the D in CRUD = Delete).
// Only accepts POST (so the URL can't be triggered by accident).

require "includes/auth.php";
require_login();
require "includes/db.php";

// Reject anything that isn't a POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: view_students.php");
    exit;
}

// Check CSRF token (stops fake delete requests)
csrf_check();

// Get the id. (int) makes sure it's a number.
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = "Invalid student ID.";
    header("Location: view_students.php");
    exit;
}

// Get the student so we can also delete their photo file
$stmt = $pdo->prepare("SELECT photo FROM students WHERE id = :id");
$stmt->execute([':id' => $id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    // Delete the photo file (if any)
    if (!empty($student['photo']) && file_exists("uploads/" . $student['photo'])) {
        unlink("uploads/" . $student['photo']);
    }

    // Delete the student (attendance + grades cascade automatically)
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
    $stmt->execute([':id' => $id]);

    $_SESSION['success'] = "Student deleted.";
} else {
    $_SESSION['error'] = "Student not found.";
}

header("Location: view_students.php");
exit;
