<?php
// ====================================================================
// ENTRY POINT
// When someone visits http://localhost:8000/ this is the file PHP runs.
// We don't show anything here — we just send them to the right page:
//   - logged in?  → dashboard.php
//   - not yet?    → login.php
// ====================================================================
 
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}

// Stop running the script — important after a redirect, otherwise PHP
// would keep executing any code that came below.
exit;
