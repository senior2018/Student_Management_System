<?php
// Entry point — when someone goes to http://localhost:8080/ this file runs.
// We just send them to the right place based on whether they're logged in.

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
