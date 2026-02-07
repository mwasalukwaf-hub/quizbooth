<?php
include '../api/db.php';

try {
    // 1. Add current_balance and points_spent to customers
    $columns = $pdo->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('current_balance', $columns)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN current_balance INT DEFAULT 0 AFTER total_points");
        echo "Added 'current_balance' to customers.<br>";
        // Initialize balance with total_points for existing records
        $pdo->exec("UPDATE customers SET current_balance = total_points");
    }
    
    if (!in_array('points_spent', $columns)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN points_spent INT DEFAULT 0 AFTER current_balance");
        echo "Added 'points_spent' to customers.<br>";
    }

    // 2. Add phone to quiz_sessions
    $columnsSessions = $pdo->query("SHOW COLUMNS FROM quiz_sessions")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('phone', $columnsSessions)) {
        $pdo->exec("ALTER TABLE quiz_sessions ADD COLUMN phone VARCHAR(20) AFTER player_name");
        echo "Added 'phone' to quiz_sessions.<br>";
    }
    
    // 3. Mark prizes as "consumable" or "unlockable"?
    // The user says "awaits until later to have more points". 
    // This implies SPENDING points. So we don't need schema change, just logic change.
    // Logic: Prize Cost = min_points.

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage();
}
