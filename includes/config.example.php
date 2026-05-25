<?php
// ====================================================================
// CONFIG TEMPLATE
// Copy this file to includes/config.php and fill in your real DB
// password. includes/config.php is in .gitignore so your real password
// won't be committed to git.
// ====================================================================
 
// 'return' sends this array back to whoever called `require` on the file.
// In db.php you'll see: $config = require __DIR__ . '/config.php';
return [
    'db' => [
        'host'     => '127.0.0.1',       // MySQL is running on this same computer
        'port'     => 3306,              // default MySQL port
        'name'     => 'student',         // the database we created in setup
        'user'     => 'root',            // your MySQL username
        'password' => 'YOUR_MYSQL_PASSWORD',  // <-- put your real password here
        'charset'  => 'utf8mb4',         // supports all characters incl. emojis
    ],
    'app' => [
        'name' => 'Student Management System',
    ],
];
