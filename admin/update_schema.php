<?php
include '../api/db.php';

try {
    // 1. Create admin_users table
    $sql = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'admin_users' checked/created.<br>";

    // 2. Create influencers table
    $sql = "CREATE TABLE IF NOT EXISTS influencers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        code VARCHAR(50) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'influencers' checked/created.<br>";

    // 3. Create default admin user if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $passHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
        $stmt->execute(['admin', $passHash]);
        echo "Default admin user created (admin/admin123).<br>";
    } else {
        echo "Admin user already exists.<br>";
    }

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage();
}
?>
