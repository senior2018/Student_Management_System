<?php
// Home page — sends the user to dashboard if logged in, login page if not

require "includes/auth.php";

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit;
