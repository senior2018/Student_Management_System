<?php
// View Students page (the R in CRUD = Read) + Search

require "includes/auth.php";
require_login();
require "includes/db.php";
require "includes/helpers.php";

// Get the search term from the URL (?q=...)
$q = trim($_GET['q'] ?? '');

if ($q != '') {
    // Wrap with % so LIKE matches anywhere in the column
    $like = '%' . $q . '%';

    // If the search is all digits, also search by id
    if (ctype_digit($q)) {
        $stmt = $pdo->prepare(
            "SELECT * FROM students
             WHERE id = :id OR fullname LIKE :like OR email LIKE :like
             ORDER BY id DESC"
        );
        $stmt->execute([':id' => (int) $q, ':like' => $like]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT * FROM students
             WHERE fullname LIKE :like OR email LIKE :like OR course LIKE :like
             ORDER BY id DESC"
        );
        $stmt->execute([':like' => $like]);
    }
} else {
    // No search — show everyone
    $stmt = $pdo->query("SELECT * FROM students ORDER BY id DESC");
}

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Work out the GPA for each student
foreach ($students as $i => $s) {
    $g = $pdo->prepare("SELECT score, credits FROM grades WHERE student_id = :sid");
    $g->execute([':sid' => $s['id']]);
    $students[$i]['gpa'] = calculate_gpa($g->fetchAll(PDO::FETCH_ASSOC));
}

$pageTitle = 'View Students';
include "partials/header.php";
?>

<h1>Students</h1>

<div class="card">
    <!-- Search box -->
    <form method="get" action="view_students.php" class="searchbar">
        <input type="text" name="q" placeholder="Search by ID, name, email, or course…" value="<?php echo e($q); ?>">
        <button type="submit" class="btn">Search</button>
        <?php if ($q != ''): ?><a class="btn btn-secondary" href="view_students.php">Clear</a><?php endif; ?>
        <a class="btn btn-right" href="add_student.php">+ Add Student</a>
    </form>

    <?php if (empty($students)): ?>
        <p>No students found.</p>
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
                <td>
                    <?php $photo = photo_url($s['photo']); ?>
                    <?php if ($photo != ''): ?>
                        <img src="<?php echo $photo; ?>" class="avatar-sm">
                    <?php else: ?>
                        <span class="text-faint">—</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $s['id']; ?></td>
                <td>
                    <?php echo e($s['fullname']); ?>
                    <?php if ($s['gpa'] !== null): ?>
                        <br><small class="text-muted">GPA: <?php echo number_format($s['gpa'], 2); ?> / 5.00</small>
                    <?php endif; ?>
                </td>
                <td><?php echo e($s['gender']); ?></td>
                <td><?php echo e($s['course']); ?></td>
                <td><?php echo e($s['email']); ?></td>
                <td><?php echo e($s['phone']); ?></td>
                <td class="actions">
                    <a class="btn btn-sm" href="edit_student.php?id=<?php echo $s['id']; ?>">Edit</a>
                    <a class="btn btn-sm btn-grades" href="grades.php?student_id=<?php echo $s['id']; ?>">Grades / GPA</a>

                    <!-- Delete uses a POST form so we can include a CSRF token -->
                    <form method="post" action="delete_student.php" class="inline-form" onsubmit="return confirm('Delete <?php echo e($s['fullname']); ?>?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                        <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include "partials/footer.php"; ?>
