<?php
// View Students page (the R in CRUD = Read) + search.
// Lists all students. Has a search box. Each row has Edit / Grades / Delete buttons.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

// Get the search term from the URL (?q=...). Empty if no search.
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    // Wrap with % so LIKE matches the text anywhere in the column
    $like = '%' . $q . '%';

    // If the search is all digits, also search by id
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
    // No search — show everyone, newest first
    $stmt = db()->query('SELECT * FROM students ORDER BY id DESC');
}

$students = $stmt->fetchAll();

// Work out the GPA for each student so we can show it in the list
foreach ($students as &$s) {
    $g = db()->prepare('SELECT score, credits FROM grades WHERE student_id = :sid');
    $g->execute([':sid' => (int) $s['id']]);
    $s['gpa'] = calculate_gpa($g->fetchAll());
}
unset($s);   // break the reference

$pageTitle = 'View Students';
include __DIR__ . '/partials/header.php';
?>

<h1>Students</h1>

<div class="card">
    <!-- Search box (uses GET so the search shows up in the URL) -->
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
                <th>Photo</th><th>ID</th><th>Full Name</th><th>Gender</th><th>Course</th>
                <th>Email</th><th>Phone</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($students as $s): ?>
            <tr>
                <td><img src="<?= e(photo_url($s['photo'] ?? null)) ?>" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover;"></td>
                <!-- (int) makes sure no HTML can sneak in via the id -->
                <td><?= (int) $s['id'] ?></td>
                <td>
                    <?= e($s['fullname']) ?>
                    <?php if (isset($s['gpa']) && $s['gpa'] !== null): ?>
                        <br><small style="color:#64748b;">GPA: <?= number_format((float) $s['gpa'], 2) ?> / 5.00</small>
                    <?php endif; ?>
                </td>
                <td><?= e($s['gender']) ?></td>
                <td><?= e($s['course']) ?></td>
                <td><?= e($s['email']) ?></td>
                <td><?= e($s['phone']) ?></td>
                <td class="actions">
                    <a class="btn btn-sm" href="edit_student.php?id=<?= (int) $s['id'] ?>">Edit</a>
                    <a class="btn btn-sm btn-grades" href="grades.php?student_id=<?= (int) $s['id'] ?>" title="Manage subjects and see GPA">Grades / GPA</a>

                    <!--
                        Delete uses a POST form (not a plain link) for two reasons:
                          1. Links could be followed by mistake.
                          2. We need to send the CSRF token, which goes in a form field.
                        confirm() pops up a "Are you sure?" prompt.
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
