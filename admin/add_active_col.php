<?php
include '../api/db.php';

try {
    // Add is_active column if it doesn't exist
    $sql = "ALTER TABLE admin_users ADD COLUMN is_active TINYINT(1) DEFAULT 1";
    try {
        $pdo->exec($sql);
        echo "Column 'is_active' added to admin_users.<br>";
    } catch (PDOException $e) {
        // Ignore if column already exists (error code 42S21 usually, or generic logic)
        echo "Column 'is_active' might already exist or error: " . $e->getMessage() . "<br>";
    }

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage();
}
?>
