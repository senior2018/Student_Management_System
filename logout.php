<?php
// Logout — wipe the session and go back to login

require "includes/auth.php";

$_SESSION = [];
session_destroy();

header("Location: login.php");
exit;
