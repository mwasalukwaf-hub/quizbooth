<?php
// Database configuration
$host = 'localhost';

// Default / Local Credentials
$db   = 'bramex_Quizzify';
$user = 'root';
$pass = ''; 

// PRODUCTION CREDENTIALS (for quizzify.bramex.co.tz)
if ($_SERVER['HTTP_HOST'] === 'quizzify.bramex.co.tz' || $_SERVER['SERVER_NAME'] === 'quizzify.bramex.co.tz') {
    // TODO: Update these with your cPanel/ Hosting details
    // Database and User often have a prefix (e.g., bramexco_Quizzify)
    $db   = 'bramex_Quizzify'; 
    $user = 'bramex_Quizzify';
    $pass = 'Bib@2012aa++'; 
}

$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// DEBUG MODE: Enable error reporting to verify credentials
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Show the actual error to debug
    http_response_code(200); // Send 200 so browser shows text
    echo "<h1>Database Connection Failed</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>User: " . $user . "</p>";
    echo "<p>DB: " . $db . "</p>";
    exit;
}
