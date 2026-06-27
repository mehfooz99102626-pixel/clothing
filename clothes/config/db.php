<?php
/**
 * db.php - Database Connection Configuration
 * Uses PDO for secure database queries and protection against SQL injection.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'clothing_store');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default empty password for typical local XAMPP/WAMP environments
define('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // In production, log error and show a generic message.
    // For development/debugging, we output the error details.
    die("Database Connection Failed. Please ensure your MySQL server is running and the database is imported.<br>Error: " . htmlspecialchars($e->getMessage()));
}
