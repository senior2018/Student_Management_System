<?php
// ====================================================================
// DATABASE CONNECTION
// Provides the db() function. Any file that calls db() gets a PDO
// object connected to the MySQL database from config.php.
// ====================================================================
 
// 'declare(strict_types=1)' makes PHP strict about argument types — if
// a function expects a string but gets an int, PHP throws an error
// instead of silently converting. Helps catch bugs.
declare(strict_types=1);

// We define a single function db() that returns a PDO connection.
function db(): PDO
{
    // 'static' means $pdo is remembered between calls to this function.
    // First call: $pdo is null → we create the connection.
    // Later calls: $pdo already exists → we just return it.
    // This way we connect to MySQL only ONCE per page load (faster).
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Find the config file (same folder as this db.php)
    $configPath = __DIR__ . '/config.php';
    if (!file_exists($configPath)) {
        http_response_code(500);   // tell browser: "server error"
        die('Missing includes/config.php — copy includes/config.example.php to includes/config.php and set your DB credentials.');
    }

    // Load the config array from config.php
    $config = require $configPath;
    $db = $config['db'];

    // Build the DSN (Data Source Name) string — it tells PDO what to connect to.
    // sprintf() fills in placeholders (%s = string, %d = number).
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['name'],
        $db['charset']
    );

    // try/catch lets us handle connection errors gracefully instead of crashing.
    try {
        $pdo = new PDO($dsn, $db['user'], $db['password'], [
            // Throw exceptions on errors (easier to debug than silent failures)
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Default fetch mode: return rows as associative arrays (column-name → value)
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Use REAL prepared statements (more secure than emulated ones)
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        // Escape the error before printing — never trust any string going to HTML
        die('Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }

    return $pdo;
}
