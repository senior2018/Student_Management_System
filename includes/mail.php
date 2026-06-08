<?php
// Email helper using PHPMailer (installed via Composer).
// Has two functions:
//   mail_config()       — get the email settings (or null if not set up)
//   send_welcome_email()— send a welcome email when a new student is added

require_once __DIR__ . '/../vendor/autoload.php';


// Get the email settings from config.php.
// Returns null if email isn't set up yet — so the app still works.
function mail_config() {
    $configPath = __DIR__ . '/config.php';
    if (!file_exists($configPath)) {
        return null;
    }
    $config = require $configPath;
    $mail = $config['mail'] ?? null;

    // Treat placeholder values as "not set up"
    if (!$mail || empty($mail['username']) || strpos($mail['username'], 'YOUR_') !== false) {
        return null;
    }
    return $mail;
}


// Send the welcome email after a new student is added.
// Returns true if sent, false if email isn't configured.
function send_welcome_email($toEmail, $toName) {
    $cfg = mail_config();
    if (!$cfg) {
        return false;   // email not set up — just skip
    }

    $m = new PHPMailer\PHPMailer\PHPMailer(true);
    $m->isSMTP();
    $m->Host       = $cfg['host'];
    $m->Port       = $cfg['port'];
    $m->SMTPAuth   = true;
    $m->Username   = $cfg['username'];
    $m->Password   = $cfg['password'];
    $m->SMTPSecure = $cfg['encryption'];   // 'tls' or 'ssl'
    $m->CharSet    = 'UTF-8';
    $m->setFrom($cfg['from_email'], $cfg['from_name']);

    $m->addAddress($toEmail, $toName);
    $m->Subject = "Welcome to the Student Management System";
    $m->isHTML(true);

    $safeName = htmlspecialchars($toName);
    $m->Body    = "<p>Hello $safeName,</p><p>Your student record has just been created.</p>";
    $m->AltBody = "Hello $toName, your student record has just been created.";

    $m->send();
    return true;
}
