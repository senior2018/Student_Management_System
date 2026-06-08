<?php
// Authentication helpers — session, login check, CSRF, output escape.
// Every page should include this file at the top.

// Start the session (only if it's not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Check if the user is logged in. If not, send to login page.
// Put this at the top of any page that should be private.
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}


// Shortcut for htmlspecialchars() — safely print user data into HTML.
// Stops people from sneaking in HTML/JavaScript (XSS attacks).
// Use it in templates by calling e($name) inside a PHP echo tag.
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ===== CSRF protection =====
// CSRF = "Cross-Site Request Forgery". Stops attackers from tricking
// a logged-in user into submitting forms from another website.
// We add a secret token to every form. On submit, we check that the
// token matches what's saved in the session.

// Get the current CSRF token (or make one on first use)
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Hidden form field with the token.
// Put this inside every form by echoing csrf_field() in a PHP tag.
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// Call this at the top of every form handler.
// If the token doesn't match, kill the request.
function csrf_check() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Invalid CSRF token. Please go back and try again.");
    }
}
