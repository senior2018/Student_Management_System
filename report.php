<?php
// PDF Report — uses the dompdf library (installed via Composer)
// dompdf turns HTML into a PDF

require "includes/auth.php";
require_login();
require "includes/db.php";
require "includes/helpers.php";
require "vendor/autoload.php";   // load Composer libraries

// Load all students and compute their GPA
$students = $pdo->query("SELECT * FROM students ORDER BY fullname")->fetchAll(PDO::FETCH_ASSOC);

foreach ($students as $i => $s) {
    $g = $pdo->prepare("SELECT score, credits FROM grades WHERE student_id = :sid");
    $g->execute([':sid' => $s['id']]);
    $students[$i]['gpa'] = calculate_gpa($g->fetchAll(PDO::FETCH_ASSOC));
}

// Build the HTML for the PDF.
// Note: this CSS is intentionally inline (not in assets/style.css) because:
//   - dompdf renders the PDF, not the browser, so it can't share web styles
//   - dompdf only supports a subset of CSS
//   - The PDF uses DejaVu Sans, a special font built into dompdf
$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
    h1 { color: #1e40af; margin: 0 0 4px 0; }
    .meta { color: #64748b; font-size: 10px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
    th { background: #e0f2fe; }
    tr:nth-child(even) td { background: #f8fafc; }
</style></head><body>';

$html .= '<h1>Student Report</h1>';
$html .= '<div class="meta">Generated ' . date('Y-m-d H:i') . ' · Total students: ' . count($students) . ' · GPA scale: TCU 5.0</div>';

$html .= '<table><thead><tr>
    <th>ID</th><th>Full Name</th><th>Gender</th><th>Course</th>
    <th>Email</th><th>Phone</th><th>GPA / 5.00</th><th>Classification</th>
</tr></thead><tbody>';

foreach ($students as $s) {
    $gpa = $s['gpa'] === null ? '—' : number_format($s['gpa'], 2);
    $html .= '<tr>';
    $html .= '<td>' . $s['id'] . '</td>';
    $html .= '<td>' . htmlspecialchars($s['fullname']) . '</td>';
    $html .= '<td>' . htmlspecialchars($s['gender']) . '</td>';
    $html .= '<td>' . htmlspecialchars($s['course']) . '</td>';
    $html .= '<td>' . htmlspecialchars($s['email']) . '</td>';
    $html .= '<td>' . htmlspecialchars($s['phone']) . '</td>';
    $html .= '<td>' . $gpa . '</td>';
    $html .= '<td>' . gpa_classification($s['gpa']) . '</td>';
    $html .= '</tr>';
}
$html .= '</tbody></table></body></html>';

// Generate the PDF
$dompdf = new Dompdf\Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Show the PDF in the browser
$dompdf->stream('students-' . date('Y-m-d') . '.pdf', ['Attachment' => false]);
exit;
