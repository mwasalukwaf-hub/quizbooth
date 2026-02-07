<?php
header('Content-Type: application/json');
include 'db.php';

$token = isset($_POST['token']) ? $_POST['token'] : '';
$influencer = isset($_POST['influencer']) ? trim($_POST['influencer']) : '';

if($token) {
    // 1. Save influencer (if provided)
    if($influencer) {
        $stmt = $pdo->prepare("UPDATE quiz_sessions SET influencer = ? WHERE token = ?");
        $stmt->execute([$influencer, $token]);
    }
    
    // 2. Get Statistics
    // Get result key of current user
    $resStmt = $pdo->prepare("SELECT result_key FROM quiz_sessions WHERE token = ?");
    $resStmt->execute([$token]);
    $resultKey = $resStmt->fetchColumn();
    
    if(!$resultKey) $resultKey = 'original';

    // Count matches
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM quiz_sessions WHERE result_key = ?");
    $countStmt->execute([$resultKey]);
    $totalKey = $countStmt->fetchColumn();
    
    // Count total players
    $allStmt = $pdo->query("SELECT COUNT(*) FROM quiz_sessions WHERE result_key IS NOT NULL");
    $totalAll = $allStmt->fetchColumn();
    
    $percent = $totalAll > 0 ? round(($totalKey / $totalAll) * 100) : 0;
    
    // Get fake "Others like you" names (in real app, query previous users)
    // We will query recent users with same result key excluding current
    $othersStmt = $pdo->prepare("SELECT player_name FROM quiz_sessions WHERE result_key = ? AND token != ? AND player_name != '' ORDER BY id DESC LIMIT 3");
    $othersStmt->execute([$resultKey, $token]);
    $others = $othersStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Fallback names if empty
    if(count($others) < 3) {
        $fallbacks = ['@toni.dar', '@dj_hyperfan', '@vibeking', '@cool_cat', '@party_monster'];
        $others = array_merge($others, array_slice($fallbacks, 0, 3 - count($others)));
    }

    echo json_encode([
        "status" => "success",
        "match_count" => $totalKey,
        "match_percent" => $percent,
        "others" => $others
    ]);
} else {
    echo json_encode(["status" => "error"]);
}
