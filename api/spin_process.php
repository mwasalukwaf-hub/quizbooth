<?php
session_start();
include '../api/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['ba_user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$site_id = $_SESSION['ba_site_id'];
$tid = filter_input(INPUT_POST, 'tid', FILTER_VALIDATE_INT);
$cid = filter_input(INPUT_POST, 'cid', FILTER_VALIDATE_INT);

$customerId = 0;
$transactionId = null;

try {
    // 1. Identify Customer & Validate
    if ($tid) {
        $stmt = $pdo->prepare("SELECT customer_id, buckets_bought FROM transactions WHERE id = ? AND site_id = ?");
        $stmt->execute([$tid, $site_id]);
        $trans = $stmt->fetch();
        if (!$trans) { echo json_encode(['error' => 'Transaction not found']); exit; }
        
        $customerId = $trans['customer_id'];
        $transactionId = $tid; // Can still link to Tx if desired
    } elseif ($cid) {
        $customerId = $cid;
    } else {
        echo json_encode(['error' => 'Invalid Request']); exit;
    }

    // 2. Refresh Current Points
    $stmt = $pdo->prepare("SELECT current_balance FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $points = $stmt->fetchColumn();

    if ($points <= 0) {
        echo json_encode(['error' => 'No points available']); exit;
    }

    // 3. Logic: Select Prize
    $stmt = $pdo->prepare("SELECT * FROM prizes WHERE min_points <= ? AND stock > 0");
    $stmt->execute([$points]);
    $prizes = $stmt->fetchAll();

    if (empty($prizes)) {
        echo json_encode(['id' => 0, 'text' => 'No prizes in this tier']); // Should define fallback prize usually
        exit;
    }

    // Weighted Random
    $totalWeight = 0;
    foreach ($prizes as $p) $totalWeight += $p['probability'];
    $rand = mt_rand(1, $totalWeight);
    $current = 0;
    $selected = null;
    foreach ($prizes as $p) {
        $current += $p['probability'];
        if ($rand <= $current) {
            $selected = $p;
            break;
        }
    }
    if (!$selected) $selected = $prizes[0];

    // 3. TRANSACTION to Record Win & DEDUCT POINTS
    $pdo->beginTransaction();
    
    // Deduct Points (Price = min_points)
    // IMPORTANT: "Awaits until later to have MORE points". This implies tiers.
    // However, does the spin COST points? Usually, yes. 
    // If I have 10 points, and I win a 5 point prize, do I lose 5 points? 
    // Or do I lose 10 points for the "Spin Opportunity"?
    // User requirement: "List of prizes will depend with the clients Point earned... also explain in which category of points each prize appear".
    // Interpretation: I 'spend' my points to get a prize of that equivalent value.
    // Strategy: We deduct the 'min_points' (value) of the *won* prize? Or the max possible?
    // RISK: If I have 100 points, and Random gives me a "1 point Sticker". I shouldn't lose 100 points.
    // BETTER MODEL: You choose to spin the "5 Point Wheel" or "10 Point Wheel".
    // GIVEN THE CODE STRUCTURE (One Big Wheel):
    // We should assume the PRIZE VALUE is deducted.
    // OR, we simply deduct the points used to "qualify" for the spin?
    // Let's go with: **Deduct the `min_points` value of the prize won**.
    
    // Cost Logic: User requested "Points are deducted if they play, whether they win or not".
    // This implies a fixed 'Spin Cost'.
    // We assume 1 Spin = 1 Point (1 Bucket).
    $cost = 1;
    
    // Check balance again to be safe
    // (Already checked above, but good for concurrency)
    
    // Update Customer: Deduct balance, add spent
    $upd = $pdo->prepare("UPDATE customers SET current_balance = current_balance - ?, points_spent = points_spent + ? WHERE id = ? AND current_balance >= ?");
    $res = $upd->execute([$cost, $cost, $customerId, $cost]);
    
    if ($upd->rowCount() == 0) {
        // Race condition or balance mismatch
        throw new Exception("Insufficient balance for this prize.");
    }

    // Decrement Stock
    $pdo->prepare("UPDATE prizes SET stock = stock - 1 WHERE id = ?")->execute([$selected['id']]);
    
    // Record Win
    $stmt = $pdo->prepare("INSERT INTO wins (site_id, customer_id, prize_id, transaction_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$site_id, $customerId, $selected['id'], $transactionId]);
    
    $pdo->commit();
    
    // Return result
    echo json_encode([
        'id' => $selected['id'],
        'text' => $selected['name'],
        'image' => $selected['image_url'],
        'remaining_points' => $points - $cost
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}
