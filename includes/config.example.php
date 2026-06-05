<?php
// This is the example config file.
// Copy this to includes/config.php and put your real password inside.
// (config.php is in .gitignore so your password stays private.)

return [
    'db' => [
        'host'     => '127.0.0.1',       // mysql is on this computer
        'port'     => 3306,              // default mysql port
        'name'     => 'student',         // our database name
        'user'     => 'root',            // your mysql username
        'password' => 'YOUR_MYSQL_PASSWORD',  // <-- put your password here
        'charset'  => 'utf8mb4',
    ],

    // Email is optional. Leave the username as 'YOUR_GMAIL_ADDRESS' to skip it.
    // To turn it on for Gmail:
    //   1. Turn on 2-step verification in your Google account
    //   2. Make an "App password" at https://myaccount.google.com/apppasswords
    //   3. Put that 16-letter app password below (not your normal password)
    'mail' => [
        'host'       => 'smtp.gmail.com',
        'port'       => 587,
        'encryption' => 'tls',
        'username'   => 'YOUR_GMAIL_ADDRESS',
        'password'   => 'YOUR_GMAIL_APP_PASSWORD',
        'from_email' => 'YOUR_GMAIL_ADDRESS',
        'from_name'  => 'Student Management System',
    ],

    'app' => [
        'name' => 'Student Management System',
    ],
];
