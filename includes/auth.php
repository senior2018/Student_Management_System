<?php
// Login / logout helpers.
// Has functions to check if someone is logged in, log them in, log them out.

// Start the session (so we can remember the user across pages).
// We check first to avoid the "session already started" warning.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,         // 0 = cookie lasts until browser closes
        'httponly' => true,      // JavaScript can't read it (safer)
        'samesite' => 'Lax',     // browser only sends it on same-site requests
    ]);
    session_start();
}

// Is someone logged in right now?
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

// Use this at the top of any page that should be private.
// If the user isn't logged in, send them to the login page.
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// Get the current user (or null if not logged in)
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
    ];
}

// Call this after we confirmed the username/password are right
function login_user($user) {
    // Make a fresh session ID (safer — prevents session fixation attacks)
    session_regenerate_id(true);

    // Save the user info into the session
    $_SESSION['user_id']  = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
}

// Wipe the session and delete its cookie
function logout_user() {
    // Clear everything in the session
    $_SESSION = [];

    // Also delete the cookie on the user's browser
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    // Destroy the session on the server
    session_destroy();
}
