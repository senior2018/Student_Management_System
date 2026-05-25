<?php
// ====================================================================
// HELPER FUNCTIONS
// Small reusable functions used everywhere: escaping output, CSRF
// tokens, flash messages, and form validation.
// ====================================================================
 
declare(strict_types=1);

// SHORTCUT for htmlspecialchars().
// Every time we print user-supplied text into HTML we must escape it
// so that characters like <, >, " can't break our HTML or run scripts.
// Usage: <?= e($student['fullname']) ?>
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ------------------------------------------------------------------
// CSRF (Cross-Site Request Forgery) protection
// ------------------------------------------------------------------
// Without this, a malicious website could trick a logged-in admin
// into submitting forms (e.g. "delete student") without realising.
// We solve it by giving every form a secret random token that only
// our own pages know. The server checks the token on submit.

// Returns the current CSRF token, creating one on the first call.
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // random_bytes() generates cryptographically secure random data.
        // bin2hex() turns it into a readable hex string.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Prints a hidden form field with the token. Drop this inside every <form>.
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Call at the top of POST handlers. If the submitted token doesn't
// match what we have in the session, the request is fake — reject it.
function csrf_verify(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    // hash_equals() compares two strings safely against timing attacks.
    if (!is_string($sent) || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(419);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// ------------------------------------------------------------------
// FLASH MESSAGES
// ------------------------------------------------------------------
// Show a one-time message after a redirect (e.g. "Student saved!").
// We save it in the session, then clear it as soon as it's shown.

function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);   // remove so it only shows once
        return $msg;
    }
    return null;
}

// ------------------------------------------------------------------
// STUDENT FORM VALIDATION
// ------------------------------------------------------------------
// Returns two things:
//   $errors  = an array of error messages keyed by field name
//   $clean   = the cleaned-up values (trimmed, etc.) to put back into the form
// If $errors is empty, all the data is valid and safe to save.
function validate_student(array $input): array
{
    $errors = [];

    // Full name: required, sensible length
    $fullname = trim($input['fullname'] ?? '');
    if ($fullname === '' || mb_strlen($fullname) < 2 || mb_strlen($fullname) > 100) {
        $errors['fullname'] = 'Full name must be between 2 and 100 characters.';
    }

    // Gender: must be one of our three allowed values
    $gender = $input['gender'] ?? '';
    if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
        $errors['gender'] = 'Please select a valid gender.';
    }

    // Course: required, max 100 chars
    $course = trim($input['course'] ?? '');
    if ($course === '' || mb_strlen($course) > 100) {
        $errors['course'] = 'Course is required (max 100 characters).';
    }

    // Email: must be a valid email format (PHP's filter handles the regex)
    $email = trim($input['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    // Phone: digits, spaces, +, -, (, ) allowed; 7–20 characters total
    $phone = trim($input['phone'] ?? '');
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $errors['phone'] = 'Phone must be 7-20 digits (numbers, spaces, +, -, () allowed).';
    }

    return [$errors, [
        'fullname' => $fullname,
        'gender'   => $gender,
        'course'   => $course,
        'email'    => $email,
        'phone'    => $phone,
    ]];
}
