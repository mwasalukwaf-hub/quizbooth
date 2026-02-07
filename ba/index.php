<?php
session_start();
if (!isset($_SESSION['ba_user_id'])) {
    header("Location: login.php");
    exit;
}

include '../api/db.php';

$site_id = $_SESSION['ba_site_id'];
$ba_id = $_SESSION['ba_user_id'];
$msg = '';

// Handle Sale Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'register_sale') {
    $name = trim($_POST['name']);
    if (empty($name)) { $msg = "Name is required"; }
    else {
        $buckets = intval($_POST['buckets']);

        if ($buckets > 0) {
            try {
                $pdo->beginTransaction();

                // 1. Get/Create Customer by NAME
                // We use name as the unique identifier as requested (though risky)
                $stmt = $pdo->prepare("SELECT id, total_points FROM customers WHERE name = ?");
                $stmt->execute([$name]);
                $customer = $stmt->fetch();

                if ($customer) {
                    $customerId = $customer['id'];
                    // Update points
                    $pdo->prepare("UPDATE customers SET total_points = total_points + ?, current_balance = current_balance + ? WHERE id = ?")
                        ->execute([$buckets, $buckets, $customerId]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO customers (name, total_points, current_balance, phone) VALUES (?, ?, ?, '')");
                    $stmt->execute([$name, $buckets, $buckets]);
                    $customerId = $pdo->lastInsertId();
                }

            // 2. Log Transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (site_id, ba_id, customer_id, buckets_bought, points_earned) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$site_id, $ba_id, $customerId, $buckets, $buckets]);
            $transactionId = $pdo->lastInsertId();

            $pdo->commit();

            // Redirect to Game with Transaction Token (or just ID)
            header("Location: game.php?tid=" . $transactionId);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Error: " . $e->getMessage();
        }
    }
    }
}

// Handle Check Balance / Play Request
// Handle Check Balance / Play Request
$customerData = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'check_balance') {
    $searchName = trim($_POST['search_name']);
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE name = ?");
    $stmt->execute([$searchName]);
    $customerData = $stmt->fetch();
    if (!$customerData) {
        $msg = "Customer '$searchName' not found.";
    }
}

// Get recent stats for today
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT SUM(buckets_bought) as buckets, COUNT(*) as sales FROM transactions WHERE ba_id = ? AND DATE(created_at) = ?");
$stmt->execute([$ba_id, $today]);
$stats = $stmt->fetch();
$bucketsToday = $stats['buckets'] ?? 0;
$salesToday = $stats['sales'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Smirnoff</title>
    <link rel="stylesheet" href="../assets/css/ba.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="ba-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div class="logo-area" style="text-align: left;">
                <h1 style="font-size: 1.5rem;">Smirnoff</h1>
                <span style="font-size: 0.8rem;"><?php echo htmlspecialchars($_SESSION['site_name']); ?></span>
            </div>
            <a href="logout.php" style="color: #666; text-decoration: none;">Logout</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $bucketsToday; ?></div>
                <div class="stat-label">Buckets Sold Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $salesToday; ?></div>
                <div class="stat-label">Sales Recorded</div>
            </div>
        </div>

        <div class="auth-card" style="margin-top: 0; text-align: left;">
            <h2 style="margin-top: 0; margin-bottom: 20px;">Register Purchase</h2>
            
            <?php if($msg): ?>
                <div style="color: red; margin-bottom: 15px;"><?php echo $msg; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="register_sale">
                
                <div class="form-group">
                    <label style="display:block; margin-bottom: 5px; color: #888;">Customer Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="Enter Exact Name (e.g. John_1)">
                </div>

                <div class="form-group">
                    <label style="display:block; margin-bottom: 5px; color: #888;">Buckets Purchased</label>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" onclick="adj(1)" style="padding: 15px; background: #333; border: 1px solid #444; color: white; border-radius: 8px;">+</button>
                        <input type="number" id="bc" name="buckets" class="form-control" value="1" min="1" style="text-align: center;">
                        <button type="button" onclick="adj(-1)" style="padding: 15px; background: #333; border: 1px solid #444; color: white; border-radius: 8px;">-</button>
                    </div>
                </div>

                <button type="submit" class="btn-neon">Proceed to Game &rarr;</button>
            </form>
        </div>

        <!-- Check Balance / Redeem Section -->
        <div class="auth-card" style="margin-top: 20px; text-align: left;">
            <h2 style="margin-top: 0; margin-bottom: 20px;">Redeem / Check Balance</h2>
            <form method="POST">
                <input type="hidden" name="action" value="check_balance">
                <div class="form-group" style="display:flex; gap:10px;">
                    <input type="text" name="search_name" class="form-control" required placeholder="Search Name..." value="<?php echo htmlspecialchars($_POST['search_name'] ?? ''); ?>">
                    <button type="submit" class="btn-neon" style="width: auto; padding: 10px 20px;">Search</button>
                </div>
            </form>

            <?php if ($customerData): ?>
                <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px; text-align: center; margin-top: 20px;">
                    <div style="font-size: 0.9rem; color: #aaa;">Balance for <?php echo htmlspecialchars($customerData['name'] ?? 'Unknown'); ?></div>
                    <div style="font-size: 2.5rem; font-weight: bold; color: var(--primary); margin: 10px 0;">
                        <?php echo $customerData['current_balance']; ?> <span style="font-size: 1rem;">Pts</span>
                    </div>
                    <?php if ($customerData['current_balance'] > 0): ?>
                        <a href="game.php?cid=<?php echo $customerData['id']; ?>" class="btn-neon" style="display:inline-block; margin-top: 10px; background: #fff; color: #000;">Play Game (Redeem)</a>
                    <?php else: ?>
                        <p style="color: #666;">No points available to redeem.</p>
                    <?php endif; ?>
                    <div class="stat-label" style="margin-top: 10px;">Total Lifetime Points: <?php echo $customerData['total_points']; ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <script>
        function adj(v) {
            let el = document.getElementById('bc');
            let val = parseInt(el.value) + v;
            if(val < 1) val = 1;
            el.value = val;
        }
    </script>
</body>
</html>
