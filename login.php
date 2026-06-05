<?php
// Login page.
// Shows a username/password form. On submit, checks if they match
// what's in the database. If yes, starts a session and goes to dashboard.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Already logged in? Go straight to dashboard
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = '';

// Did the user submit the form?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check the CSRF token (stops fake form submissions)
    csrf_verify();

    // Read the form values
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        // Look up the user (prepared statement = safe from SQL injection)
        $stmt = db()->prepare('SELECT id, username, password FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        // password_verify checks the typed password against the saved hash
        if ($user && password_verify($password, $user['password'])) {
            // Match! Log them in and go to dashboard
            login_user($user);
            header('Location: dashboard.php');
            exit;
        }

        // We use the same message for "wrong username" and "wrong password"
        // so attackers can't tell which usernames exist.
        $error = 'Invalid username or password.';
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="card">
        <h2>Admin Login</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" autocomplete="off">

            <!-- Hidden CSRF token (matches what's saved in the session) -->
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="username">Username</label>
                <!-- Keep the username filled in if there was an error -->
                <input id="username" name="username" type="text" required value="<?= e($username) ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <!-- Never put the password back into the form, even on error -->
                <input id="password" name="password" type="password" required>
            </div>
            <button type="submit" class="btn">Sign in</button>
        </form>
    </div>
</div>
</body>
</html>
