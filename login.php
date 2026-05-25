<?php
// ====================================================================
// LOGIN PAGE
// Shows a username/password form. On submit, checks the credentials
// against the `users` table and starts a session if they're correct.
// ====================================================================
 
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// If somebody's already logged in, don't bother showing the form —
// take them straight to the dashboard.
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

// These hold messages/values to send back to the form.
$error = '';
$username = '';
 
// Did the user submit the form?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check — make sure this submit actually came from our form.
    csrf_verify();

    // Read the form values
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        // Look up the user by username. Prepared statement = SQL-injection safe.
        $stmt = db()->prepare('SELECT id, username, password FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        // password_verify() takes the password the user typed and the hash
        // stored in the database, and tells us whether they match.
        if ($user && password_verify($password, $user['password'])) {
            // Correct! Save them into the session and redirect.
            login_user($user);
            header('Location: dashboard.php');
            exit;
        }

        // Generic message — don't say "wrong username" vs "wrong password",
        // so attackers can't tell whether a username exists.
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

        <!-- autocomplete="off" tells the browser not to remember these fields -->
        <form method="post" action="login.php" autocomplete="off">

            <!-- Hidden CSRF token — the matching value lives in the session -->
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="username">Username</label>
                <!-- Re-fill the username if there was an error, so the user doesn't have to retype -->
                <input id="username" name="username" type="text" required value="<?= e($username) ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <!-- We never echo the password back into the form, even on error -->
                <input id="password" name="password" type="password" required>
            </div>
            <button type="submit" class="btn">Sign in</button>
        </form>
    </div>
</div>
</body>
</html>
