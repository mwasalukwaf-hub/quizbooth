<?php
include __DIR__ . '/../api/db.php';

try {
    // Check if is_active exists in quiz_options
    $columns = $pdo->query("SHOW COLUMNS FROM quiz_options")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('is_active', $columns)) {
        // Add is_active column, default to 1 (active)
        $pdo->exec("ALTER TABLE quiz_options ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER result_key");
        echo "Added 'is_active' to quiz_options.<br>";
    } else {
        echo "'is_active' already exists in quiz_options.<br>";
    }

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage();
}
?>
