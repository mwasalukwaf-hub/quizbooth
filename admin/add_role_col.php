<?php
include '../api/db.php';

try {
    // Add role column if it doesn't exist
    $sql = "ALTER TABLE admin_users ADD COLUMN role VARCHAR(20) DEFAULT 'user'";
    try {
        $pdo->exec($sql);
        echo "Column 'role' added to admin_users.<br>";
        
        // Update the default admin to be 'admin' role if exists
        $stmt = $pdo->prepare("UPDATE admin_users SET role = 'admin' WHERE username = 'admin'");
        $stmt->execute();
        echo "Updated default 'admin' user to role 'admin'.<br>";
        
    } catch (PDOException $e) {
        echo "Column 'role' might already exist or error: " . $e->getMessage() . "<br>";
    }

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage();
}
?>
