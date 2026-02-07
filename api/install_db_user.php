<?php
// This script attempts to create the database user using root credentials (common in XAMPP)
$host = 'localhost';
$root_user = 'root';
$root_pass = ''; // Default XAMPP password is empty

try {
    $pdo = new PDO("mysql:host=$host", $root_user, $root_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected as root...<br>";

    // Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS bramex_Quizzify");
    echo "Database 'bramex_Quizzify' confirmed.<br>";

    // Create User
    $pdo->exec("CREATE USER IF NOT EXISTS 'bramex_Quizzify'@'localhost'");
    echo "User 'bramex_Quizzify' created (if not existed).<br>";

    // Grant Privileges
    $pdo->exec("GRANT ALL PRIVILEGES ON bramex_Quizzify.* TO 'bramex_Quizzify'@'localhost'");
    $pdo->exec("FLUSH PRIVILEGES");
    echo "Privileges granted.<br>";

    echo "<h3>Success! You can now use the app.</h3>";
    echo "<a href='../index.html'>Go to Quiz</a>";

} catch (PDOException $e) {
    echo "<h2>Error</h2>";
    echo "Could not connect as root to create the user. <br>";
    echo "Error: " . $e->getMessage() . "<br><br>";
    echo "Please manually run the SQL found in setup.sql in your database manager.";
}
