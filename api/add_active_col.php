<?php
include 'db.php';

try {
    // Add is_active column to quiz_questions table
    $sql = "ALTER TABLE quiz_questions ADD COLUMN IF NOT EXISTS is_active TINYINT DEFAULT 1;";
    $pdo->exec($sql);
    echo "<h1>Schema Updated Successfully!</h1>";
    echo "<p>Column 'is_active' added to 'quiz_questions'.</p>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
