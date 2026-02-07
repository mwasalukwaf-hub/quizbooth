<?php
include '../api/db.php';

try {
    // 1. Create SITES table
    $sql = "CREATE TABLE IF NOT EXISTS sites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(50) UNIQUE NOT NULL, -- For QR codes/URLs
        location VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'sites' checked/created.<br>";

    // 2. Add site_id and role to admin_users (transforming it into a general users table)
    // First check if columns exist to avoid errors
    $columns = $pdo->query("SHOW COLUMNS FROM admin_users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('role', $columns)) {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN role VARCHAR(20) DEFAULT 'admin' AFTER username");
        echo "Added 'role' to admin_users.<br>";
    }
    if (!in_array('site_id', $columns)) {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN site_id INT NULL AFTER role");
        $pdo->exec("ALTER TABLE admin_users ADD CONSTRAINT fk_user_site FOREIGN KEY (site_id) REFERENCES sites(id)");
        echo "Added 'site_id' to admin_users.<br>";
    }

    // 3. Create PRIZES table
    // category represents the 'tier' (e.g., '1_bucket', '2_buckets') or logic handles that.
    // Let's use 'min_points' to define eligibility. 1 bucket = 1 point roughly.
    $sql = "CREATE TABLE IF NOT EXISTS prizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        image_url VARCHAR(255),
        min_points INT DEFAULT 1, -- Minimum points/buckets required to unlock this slice
        probability INT DEFAULT 10, -- Weight for random selection (0-100 relative)
        stock INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'prizes' checked/created.<br>";

    // 4. Create CUSTOMERS table (Tracks global unique users by phone)
    $sql = "CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) UNIQUE NOT NULL,
        name VARCHAR(100),
        total_points INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'customers' checked/created.<br>";

    // 5. Create SALES/TRANSACTIONS table
    $sql = "CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT,
        ba_id INT, -- User ID of the BA
        customer_id INT,
        buckets_bought INT DEFAULT 1,
        points_earned INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (site_id) REFERENCES sites(id),
        FOREIGN KEY (ba_id) REFERENCES admin_users(id),
        FOREIGN KEY (customer_id) REFERENCES customers(id)
    )";
    $pdo->exec($sql);
    echo "Table 'transactions' checked/created.<br>";

    // 6. Create WINS table
    $sql = "CREATE TABLE IF NOT EXISTS wins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT,
        customer_id INT,
        prize_id INT,
        transaction_id INT, -- Link to the purchase that allowed this spin
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (site_id) REFERENCES sites(id),
        FOREIGN KEY (customer_id) REFERENCES customers(id),
        FOREIGN KEY (prize_id) REFERENCES prizes(id),
        FOREIGN KEY (transaction_id) REFERENCES transactions(id)
    )";
    $pdo->exec($sql);
    echo "Table 'wins' checked/created.<br>";
    
    // Seed some initial data if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM sites");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO sites (name, slug, location) VALUES ('Samaki Samaki', 'samaki-samaki', 'Masaki'), ('Elements', 'elements', 'Masaki')");
        echo "Seeded Sites.<br>";
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM prizes");
    if ($stmt->fetchColumn() == 0) {
        // Name, Image, MinPoints, Probability, Stock
        $sql = "INSERT INTO prizes (name, min_points, probability, stock) VALUES 
            ('Try Again', 1, 50, 9999),
            ('Key Holder', 1, 30, 100),
            ('T-Shirt', 2, 15, 50),
            ('Cap', 2, 15, 50),
            ('JBL Speaker', 5, 2, 5),
            ('Bottle of Smirnoff', 3, 10, 20)";
        $pdo->exec($sql);
        echo "Seeded Prizes.<br>";
    }

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage();
}
