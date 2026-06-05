<?php
// Email helper using PHPMailer.
// The rest of the app just calls send_welcome_email() — this file
// handles the messy SMTP stuff for us.

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

// Get the email settings from config.php.
// Returns null if email isn't set up yet — so the app keeps working
// even on a machine without email configured.
function mail_config() {
    $configPath = __DIR__ . '/config.php';
    if (!file_exists($configPath)) {
        return null;
    }
    $config = require $configPath;
    $mail = $config['mail'] ?? null;

    // Treat the placeholder as "not set up"
    if (!$mail || empty($mail['username']) || str_contains($mail['username'], 'YOUR_')) {
        return null;
    }
    return $mail;
}

// Make a PHPMailer ready to send. Returns null if email is off.
function build_mailer() {
    $cfg = mail_config();
    if (!$cfg) {
        return null;
    }

    $m = new PHPMailer(true);   // true = throw exception on error
    $m->isSMTP();
    $m->Host       = $cfg['host'];
    $m->Port       = (int) $cfg['port'];
    $m->SMTPAuth   = true;
    $m->Username   = $cfg['username'];
    $m->Password   = $cfg['password'];
    // tls for port 587, ssl for port 465 (the two common ones)
    $m->SMTPSecure = $cfg['encryption'] === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $m->CharSet    = 'UTF-8';
    $m->setFrom($cfg['from_email'], $cfg['from_name'] ?? 'Student Management System');
    return $m;
}

// Send the welcome email after a student is added.
// Returns true if sent, false if email is off.
function send_welcome_email($toEmail, $toName) {
    $m = build_mailer();
    if (!$m) {
        return false;   // email isn't set up — just skip
    }

    $m->addAddress($toEmail, $toName);
    $m->Subject = 'Welcome to the Student Management System';
    $m->isHTML(true);

    // Escape the name to be safe in HTML
    $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
    $m->Body = "
        <p>Hello {$safeName},</p>
        <p>Your student record has just been created in the Student Management System.</p>
        <p>If you didn't expect this email, please contact your administrator.</p>
    ";
    // Plain-text version for old email clients
    $m->AltBody = "Hello {$toName},\n\nYour student record has just been created.";

    $m->send();
    return true;
}
