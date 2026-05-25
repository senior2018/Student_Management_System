<?php
// ====================================================================
// VIEW STUDENTS (the "R" in CRUD — Read) + SEARCH
// Lists every student, with a search box that filters by ID, name,
// email, or course. Each row has Edit / Delete buttons.
// ====================================================================

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();
  
// Grab the search term from the URL (?q=...). Empty string if not set.
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    // Wrap with % wildcards so LIKE matches the text anywhere in the column
    $like = '%' . $q . '%';

    // ctype_digit() returns true if the string is purely digits.
    // If it is, we also search by id.
    if (ctype_digit($q)) {
        $stmt = db()->prepare(
            'SELECT * FROM students
             WHERE id = :id OR fullname LIKE :like OR email LIKE :like
             ORDER BY id DESC'
        );
        $stmt->execute([':id' => (int) $q, ':like' => $like]);
    } else {
        $stmt = db()->prepare(
            'SELECT * FROM students
             WHERE fullname LIKE :like OR email LIKE :like OR course LIKE :like
             ORDER BY id DESC'
        );
        $stmt->execute([':like' => $like]);
    }
} else {
    // No search — list everyone, newest first
    $stmt = db()->query('SELECT * FROM students ORDER BY id DESC');
}

// fetchAll() returns ALL matching rows as an array
$students = $stmt->fetchAll();

$pageTitle = 'View Students';
include __DIR__ . '/partials/header.php';
?>

<h1>Students</h1>

<div class="card">
    <!-- Search form uses GET so the search term appears in the URL (?q=…) -->
    <form method="get" action="view_students.php" class="searchbar">
        <input type="text" name="q" placeholder="Search by ID, name, email, or course…" value="<?= e($q) ?>">
        <button class="btn" type="submit">Search</button>
        <?php if ($q !== ''): ?><a class="btn btn-secondary" href="view_students.php">Clear</a><?php endif; ?>
        <a class="btn" href="add_student.php" style="margin-left:auto;">+ Add Student</a>
    </form>

    <?php if (!$students): ?>
        <p>No students found<?= $q !== '' ? ' matching "' . e($q) . '"' : '' ?>.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Full Name</th><th>Gender</th><th>Course</th>
                <th>Email</th><th>Phone</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($students as $s): ?>
            <tr>
                <!-- Casting IDs to int makes it impossible to inject HTML here -->
                <td><?= (int) $s['id'] ?></td>
                <td><?= e($s['fullname']) ?></td>
                <td><?= e($s['gender']) ?></td>
                <td><?= e($s['course']) ?></td>
                <td><?= e($s['email']) ?></td>
                <td><?= e($s['phone']) ?></td>
                <td class="actions">
                    <a class="btn btn-sm" href="edit_student.php?id=<?= (int) $s['id'] ?>">Edit</a>

                    <!--
                        Delete uses a POST form (not a link), because:
                          1. Browsers / search bots could follow a GET link by accident.
                          2. We need to send the CSRF token, which lives in a form field.
                        onsubmit="return confirm(…)" pops up a confirmation prompt.
                    -->
                    <form method="post" action="delete_student.php" onsubmit="return confirm('Delete <?= e($s['fullname']) ?>?');" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
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
