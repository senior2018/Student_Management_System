<?php
// Helper functions used everywhere in the app.
// - e()                : safely print stuff to HTML
// - csrf_*             : form security tokens
// - flash_*            : one-time messages after a redirect
// - validate_student() : check the student form fields
// - photo_*            : handle photo uploads
// - GPA functions      : calculate the student's GPA

// Shortcut for htmlspecialchars().
// Use this whenever we print user data into HTML — it stops people
// from sneaking in HTML or JavaScript (this is called XSS).
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ===== CSRF =====
// CSRF means "fake form submission from another website".
// We give every form a secret random token. If the token coming back
// doesn't match what we saved, we reject the request.

// Get the current CSRF token (or make one if there isn't one yet)
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));  // long random string
    }
    return $_SESSION['csrf_token'];
}

// Hidden form field with the token. Put this inside every form.
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Call this at the top of every form handler.
// If the token doesn't match, kill the request.
function csrf_verify() {
    $sent = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(419);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// ===== FLASH MESSAGES =====
// Save a message in the session, show it on the next page, then remove it.
// Useful for "Student saved!" notices after a redirect.

function flash_set($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

function flash_get($key) {
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);   // remove so it only shows once
        return $msg;
    }
    return null;
}

// ===== STUDENT FORM VALIDATION =====
// Check the form fields. Returns:
//   - $errors: array of error messages (empty if all good)
//   - $clean:  the cleaned-up values to put back into the form
function validate_student($input) {
    $errors = [];

    // Full name: required, between 2 and 100 letters
    $fullname = trim($input['fullname'] ?? '');
    if ($fullname === '' || strlen($fullname) < 2 || strlen($fullname) > 100) {
        $errors['fullname'] = 'Full name must be between 2 and 100 characters.';
    }

    // Gender: must be one of three values
    $gender = $input['gender'] ?? '';
    if (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors['gender'] = 'Please pick a gender.';
    }

    // Course: required, max 100 chars
    $course = trim($input['course'] ?? '');
    if ($course === '' || strlen($course) > 100) {
        $errors['course'] = 'Course is required (max 100 characters).';
    }

    // Email: must look like an email
    $email = trim($input['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    // Phone: digits/spaces/+/-/(/) only, 7 to 20 characters
    $phone = trim($input['phone'] ?? '');
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $errors['phone'] = 'Phone must be 7-20 characters (numbers, spaces, +, -, () allowed).';
    }

    return [$errors, [
        'fullname' => $fullname,
        'gender'   => $gender,
        'course'   => $course,
        'email'    => $email,
        'phone'    => $phone,
    ]];
}

// ===== PHOTO UPLOAD =====
// Handles a photo coming from $_FILES['photo'].
// Returns the filename to save, or null if no upload.
// Throws if the file is too big or wrong type.
function handle_photo_upload($file, $oldPhoto = null) {
    // No new file? Keep whatever the student already had
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $oldPhoto;
    }

    // Some other upload problem from PHP
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Photo upload failed.');
    }

    // Size limit: 2 MB
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Photo must be 2 MB or smaller.');
    }

    // Check the actual file type (don't trust what the browser says)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Photo must be JPG, PNG, GIF, or WebP.');
    }
    $ext = $allowed[$mime];

    // Make a unique filename so files don't overwrite each other
    $newName = 'student_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = __DIR__ . '/../uploads/' . $newName;

    // move_uploaded_file is the safe way to save uploads
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save the photo.');
    }

    // Delete the old photo (if any) to save disk space
    if ($oldPhoto) {
        $oldPath = __DIR__ . '/../uploads/' . $oldPhoto;
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }

    return $newName;
}

// Get the URL for a photo (or a default avatar if no photo)
function photo_url($filename) {
    if ($filename && file_exists(__DIR__ . '/../uploads/' . $filename)) {
        return 'uploads/' . rawurlencode($filename);
    }
    // Tiny placeholder avatar drawn with SVG
    return 'data:image/svg+xml;utf8,' . rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">' .
        '<rect width="64" height="64" fill="#cbd5e1"/>' .
        '<circle cx="32" cy="24" r="12" fill="#94a3b8"/>' .
        '<ellipse cx="32" cy="56" rx="22" ry="14" fill="#94a3b8"/>' .
        '</svg>'
    );
}

// ===== GPA (Tanzania TCU 5.0 scale) =====
// Turn a score (0-100) into a TCU grade point.
//   70% - 100% : A  = 5.0  (Excellent)
//   60% -  69% : B+ = 4.0  (Very Good)
//   50% -  59% : B  = 3.0  (Good)            ← minimum pass mark
//   40% -  49% : C  = 2.0  (Satisfactory)
//   35% -  39% : D  = 1.0  (Marginal Fail)
//    0% -  34% : F  = 0.0  (Absolute Fail)
function score_to_gpa($score) {
    if ($score >= 70) return 5.0;
    if ($score >= 60) return 4.0;
    if ($score >= 50) return 3.0;
    if ($score >= 40) return 2.0;
    if ($score >= 35) return 1.0;
    return 0.0;
}

// Turn a score (0-100) into a letter grade for display
function score_to_letter($score) {
    if ($score >= 70) return 'A';
    if ($score >= 60) return 'B+';
    if ($score >= 50) return 'B';
    if ($score >= 40) return 'C';
    if ($score >= 35) return 'D';
    return 'F';
}

// Work out the GPA from a list of grades (TCU formula).
//   Quality Points (QP) = Grade Point × Credit Units
//   GPA = sum(QP) / sum(Credit Units)
function calculate_gpa($grades) {
    $totalQualityPoints = 0;
    $totalCredits = 0;
    foreach ($grades as $g) {
        $credits = (int) $g['credits'];
        $totalQualityPoints += score_to_gpa((float) $g['score']) * $credits;
        $totalCredits += $credits;
    }
    if ($totalCredits === 0) {
        return null;   // no grades yet
    }
    return $totalQualityPoints / $totalCredits;
}

// Turn a GPA into a TCU degree classification
//   4.4 - 5.0 : First Class Honours
//   3.5 - 4.3 : Upper Second Class Honours
//   2.7 - 3.4 : Lower Second Class Honours
//   2.0 - 2.6 : Pass
//   below 2.0 : Fail
function gpa_classification($gpa) {
    if ($gpa === null) return '—';
    if ($gpa >= 4.4) return 'First Class Honours';
    if ($gpa >= 3.5) return 'Upper Second Class Honours';
    if ($gpa >= 2.7) return 'Lower Second Class Honours';
    if ($gpa >= 2.0) return 'Pass';
    return 'Fail';
}
