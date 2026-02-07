<?php
include 'db.php';

try {
    echo "Checking schema...<br>";
    
    // Check quiz_questions for is_active
    try {
        $pdo->query("SELECT is_active FROM quiz_questions LIMIT 1");
        echo "quiz_questions.is_active exists.<br>";
    } catch (Exception $e) {
        echo "Adding is_active to quiz_questions...<br>";
        $pdo->exec("ALTER TABLE quiz_questions ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    }

    // Check quiz_options for is_active
    try {
        $pdo->query("SELECT is_active FROM quiz_options LIMIT 1");
        echo "quiz_options.is_active exists.<br>";
    } catch (Exception $e) {
        echo "Adding is_active to quiz_options...<br>";
        $pdo->exec("ALTER TABLE quiz_options ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    }

    echo "Schema update complete.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
