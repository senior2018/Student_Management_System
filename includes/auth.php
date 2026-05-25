<?php
// ====================================================================
// AUTHENTICATION HELPERS
// Functions for sessions, login, logout, and protecting pages so that
// only logged-in users can see them.
// ====================================================================
 
declare(strict_types=1);

// A session lets us remember the user across page loads.
// session_start() must be called BEFORE any HTML is sent.
// We check the current status first to avoid the "session already started"
// warning if some other file already started it.
if (session_status() === PHP_SESSION_NONE) {
    // Configure the session cookie:
    //   httponly = JavaScript can't read it (protects against XSS-based theft)
    //   samesite = browser only sends it on same-site requests (protects against CSRF)
    session_set_cookie_params([
        'lifetime' => 0,        // 0 = cookie lasts only until the browser closes
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Returns true if a user_id is stored in the session = someone is logged in.
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

// Used at the top of any page that should be private (e.g. dashboard).
// If the user is NOT logged in, send them to login.php and stop the page.
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// Returns the current user as an array, or null if no one is logged in.
function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
    ];
}

// Call this after we've confirmed a username/password is correct.
function login_user(array $user): void
{
    // Generate a brand-new session ID and delete the old one.
    // This prevents "session fixation" attacks where an attacker tries
    // to set your session ID to something they already know.
    session_regenerate_id(true);

    // Save the user's ID and name into the session so other pages know
    // who is logged in. We store as little as possible — never the password.
    $_SESSION['user_id']  = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
}

// Wipe the session and delete its cookie completely.
function logout_user(): void
{
    // Clear every value stored in $_SESSION
    $_SESSION = [];

    // Also delete the session cookie on the user's browser
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    // Destroy the session data on the server
    session_destroy();
}
