<?php
// Delete Student page (the D in CRUD = Delete).
// Only accepts POST (with a CSRF token) — so the delete URL can't be
// triggered by accident or by a malicious link.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

// If somebody opens this URL with a GET, just send them back.
// Deletes only happen via the form on view_students.php (POST).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: view_students.php');
    exit;
}

// Check CSRF token (stops fake delete requests from other websites)
csrf_verify();

// (int) makes sure it's a number — even garbage becomes 0
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    flash_set('error', 'Invalid student ID.');
    header('Location: view_students.php');
    exit;
}

// Delete the student.
// The attendance + grades tables have ON DELETE CASCADE, so the
// student's attendance and grades will be removed automatically.
$stmt = db()->prepare('DELETE FROM students WHERE id = :id');
$stmt->execute([':id' => $id]);

// rowCount tells us how many rows got deleted
if ($stmt->rowCount() > 0) {
    flash_set('success', 'Student deleted.');
} else {
    flash_set('error', 'Student not found or already deleted.');
}

header('Location: view_students.php');
exit;
