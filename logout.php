<?php
// Logout — kill the session and go back to login

require_once __DIR__ . '/includes/auth.php';

logout_user();

header('Location: login.php');
exit;
