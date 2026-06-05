<?php
// PDF Report page.
// Makes a PDF list of all students using a library called dompdf.
// dompdf turns an HTML string into a PDF — so we build a small
// HTML page below and pass it to dompdf.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
// Composer's autoloader gives us access to the Dompdf class
require_once __DIR__ . '/vendor/autoload.php';
require_login();

use Dompdf\Dompdf;
use Dompdf\Options;

// Load all students with their GPA
$students = db()->query('SELECT * FROM students ORDER BY fullname')->fetchAll();
foreach ($students as &$s) {
    $g = db()->prepare('SELECT score, credits FROM grades WHERE student_id = :sid');
    $g->execute([':sid' => (int) $s['id']]);
    $s['gpa'] = calculate_gpa($g->fetchAll());
}
unset($s);

// Build the HTML that goes into the PDF.
// ob_start() captures everything we print until ob_get_clean().
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        /* Inline CSS — dompdf supports a useful subset of CSS */
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { color: #1e40af; margin: 0 0 4px 0; }
        .meta { color: #64748b; font-size: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #e0f2fe; }
        tr:nth-child(even) td { background: #f8fafc; }
    </style>
</head>
<body>
    <h1>Student Report</h1>
    <div class="meta">
        Generated <?= date('Y-m-d H:i') ?> · Total students: <?= count($students) ?> · GPA scale: TCU 5.0
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Full Name</th><th>Gender</th><th>Course</th>
                <th>Email</th><th>Phone</th><th>GPA / 5.00</th><th>Classification</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($students as $s): ?>
            <tr>
                <td><?= (int) $s['id'] ?></td>
                <td><?= e($s['fullname']) ?></td>
                <td><?= e($s['gender']) ?></td>
                <td><?= e($s['course']) ?></td>
                <td><?= e($s['email']) ?></td>
                <td><?= e($s['phone']) ?></td>
                <td><?= $s['gpa'] === null ? '—' : number_format($s['gpa'], 2) ?></td>
                <td><?= e(gpa_classification($s['gpa'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
<?php
$html = ob_get_clean();   // grab the HTML we just built

// Set up dompdf
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', false);   // we don't need remote files

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');   // landscape fits more columns
$dompdf->render();

// stream() sends the PDF to the browser.
//   'Attachment' => false  means "show in browser tab"
//   change to true if you want it to download instead
$dompdf->stream('students-' . date('Y-m-d') . '.pdf', ['Attachment' => false]);
exit;
