<?php
include 'db.php';
header('Content-Type: application/json');

$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
$cid = isset($_GET['cid']) ? intval($_GET['cid']) : 0;

$points = 0;

// Case 1: Direct Transaction Link (Old flow, but assuming immediate play)
if ($tid) {
    $stmt = $pdo->prepare("SELECT buckets_bought FROM transactions WHERE id = ?");
    $stmt->execute([$tid]);
    $t = $stmt->fetch();
    if ($t) { 
        $points = $t['buckets_bought']; 
        // NOTE: In the new flow, we prefer using Customer Balance. 
        // But for backward compatibility or direct transaction flow, we might keep this.
        // HOWEVER, "Client ... awaits until later to have more points". 
        // This implies we should ALWAYS check the customer's CURRENT BALANCE, not just the transaction.
        
        // Let's see if we can get the customer from the transaction
        $stmtC = $pdo->prepare("SELECT customer_id FROM transactions WHERE id = ?");
        $stmtC->execute([$tid]);
        $custId = $stmtC->fetchColumn();
        if ($custId) {
            $stmtBal = $pdo->prepare("SELECT current_balance FROM customers WHERE id = ?");
            $stmtBal->execute([$custId]);
            $points = $stmtBal->fetchColumn();
        }
    }
}
// Case 2: Customer ID directly (Redeem flow)
elseif ($cid) {
    $stmt = $pdo->prepare("SELECT current_balance FROM customers WHERE id = ?");
    $stmt->execute([$cid]);
    $points = $stmt->fetchColumn();
}

if (!$points) {
    // If 0 points, return empty or dummy 'Try Again' if configured for 0 points?
    // Usually prizes require >= 1 point.
    // If points is 0, user shouldn't satisfy min_points=1.
    echo json_encode([]); 
    exit; 
}

// Get eligible prizes
// We only show prizes they can AFFORD (min_points <= balance)
$stmt = $pdo->prepare("SELECT id, name, probability, min_points FROM prizes WHERE min_points <= ? AND stock > 0");
$stmt->execute([$points]);
$prizes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($prizes);
