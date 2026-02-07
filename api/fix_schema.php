<?php
include 'db.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS sites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        slug VARCHAR(100),
        location VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS influencers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        code VARCHAR(50) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE,
        password_hash VARCHAR(255),
        role VARCHAR(20) DEFAULT 'user',
        is_active TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS prizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        min_points INT DEFAULT 0,
        probability INT DEFAULT 10,
        stock INT DEFAULT 0
    );
    
    -- Add columns to existing tables if missing
    -- ALTER TABLE quiz_sessions ADD COLUMN IF NOT EXISTS influencer VARCHAR(50);
    ";

    $pdo->exec($sql);
    echo "<h1>Database Schema Updated Successfully!</h1>";
    echo "<p>Table 'sites' and others created.</p>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
