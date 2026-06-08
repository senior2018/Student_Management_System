<?php
// Small helper functions:
//   - photo_url()         : where to find a student's photo
//   - score_to_gpa()      : turn a score into a grade point (TCU 5.0 scale)
//   - score_to_letter()   : turn a score into a letter grade
//   - calculate_gpa()     : weighted GPA from a list of grades
//   - gpa_classification(): degree honours classification


// Get the URL for a student's photo, or empty string if none.
// ?string means: a string, or null (because some students have no photo)
function photo_url(?string $filename): string {
    if ($filename && file_exists(__DIR__ . '/../uploads/' . $filename)) {
        return 'uploads/' . $filename;
    }
    return '';
}


// ===== TCU 5.0-point GPA scale (Tanzania) =====
//   70 - 100 : A  = 5.0  (Excellent)
//   60 -  69 : B+ = 4.0  (Very Good)
//   50 -  59 : B  = 3.0  (Good)            ← minimum pass mark
//   40 -  49 : C  = 2.0  (Satisfactory)
//   35 -  39 : D  = 1.0  (Marginal Fail)
//    0 -  34 : F  = 0.0  (Absolute Fail)

// float = decimal number (like 85.5)
function score_to_gpa(float $score): float {
    if ($score >= 70) return 5.0;
    if ($score >= 60) return 4.0;
    if ($score >= 50) return 3.0;
    if ($score >= 40) return 2.0;
    if ($score >= 35) return 1.0;
    return 0.0;
}

function score_to_letter(float $score): string {
    if ($score >= 70) return 'A';
    if ($score >= 60) return 'B+';
    if ($score >= 50) return 'B';
    if ($score >= 40) return 'C';
    if ($score >= 35) return 'D';
    return 'F';
}

// GPA = sum(Grade Point × Credits) / sum(Credits)
// Returns null if the student has no grades yet
function calculate_gpa(array $grades): ?float {
    $totalPoints = 0;
    $totalCredits = 0;
    foreach ($grades as $g) {
        $credits = (int) $g['credits'];
        $totalPoints += score_to_gpa((float) $g['score']) * $credits;
        $totalCredits += $credits;
    }
    if ($totalCredits == 0) {
        return null;   // no grades yet
    }
    return $totalPoints / $totalCredits;
}

// Degree classification based on the GPA.
// ?float means: a number, or null (when the student has no grades yet)
function gpa_classification(?float $gpa): string {
    if ($gpa === null) return '—';
    if ($gpa >= 4.4) return 'First Class Honours';
    if ($gpa >= 3.5) return 'Upper Second Class Honours';
    if ($gpa >= 2.7) return 'Lower Second Class Honours';
    if ($gpa >= 2.0) return 'Pass';
    return 'Fail';
}
