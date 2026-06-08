<?php
// Login page

require "includes/auth.php";
require "includes/db.php";

// Already logged in? Go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$username = "";

if (isset($_POST['login'])) {
    // Check CSRF token first (stops fake form submissions)
    csrf_check();

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username == "" || $password == "") {
        $error = "Please enter both username and password.";
    } else {
        // Look up the user (prepared statement = safe from SQL injection)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // password_verify checks the typed password against the saved hash
        if ($user && password_verify($password, $user['password'])) {
            // Make a brand-new session ID (protects against session-fixation attacks)
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="card">
        <h2>Admin Login</h2>

        <?php if ($error != ""): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo e($username); ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login" class="btn">Sign in</button>
        </form>
    </div>
</div>
</body>
</html>
