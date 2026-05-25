<?php
// ====================================================================
// LOGOUT
// Ends the user's session and sends them back to the login page.
// ====================================================================

require_once __DIR__ . '/includes/auth.php';
 
// Wipe the session and the session cookie
logout_user();

// Send them back to login.php
header('Location: login.php');
exit;
 