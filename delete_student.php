<?php
// ====================================================================
// DELETE STUDENT (the "D" in CRUD — Delete)
// Only accepts POST (with a CSRF token) so the URL can't be triggered
// by accident or by a malicious link.
// ====================================================================
 
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

// If someone visits this URL directly with a GET, just send them back.
// Deletes only happen via the form on view_students.php (POST).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: view_students.php');
    exit;
}
 
// CSRF check — protects against fake delete requests from other sites
csrf_verify();

// Cast to int so even garbage input becomes 0 (safe to pass to SQL)
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    flash_set('error', 'Invalid student ID.');
    header('Location: view_students.php');
    exit;
}

// Delete the student. Thanks to "ON DELETE CASCADE" in the attendance
// table, their attendance rows are removed automatically.
$stmt = db()->prepare('DELETE FROM students WHERE id = :id');
$stmt->execute([':id' => $id]);

// rowCount() tells us how many rows were affected
if ($stmt->rowCount() > 0) {
    flash_set('success', 'Student deleted.');
} else {
    flash_set('error', 'Student not found or already deleted.');
}

header('Location: view_students.php');
exit;
