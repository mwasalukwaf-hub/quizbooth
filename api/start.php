<?php
header('Content-Type: application/json');
include 'db.php';

// Get quiz_id from input or default to 1
$data = json_decode(file_get_contents("php://input"), true);
$quiz_id = isset($data['quiz_id']) ? intval($data['quiz_id']) : 1;
$player_name = isset($data['player_name']) ? trim($data['player_name']) : '';

// Generate a unique token
$token = uniqid("quiz_");
$device = $_SERVER['HTTP_USER_AGENT'];
$ip_address = $_SERVER['REMOTE_ADDR'];

try {
    // We reuse the 'influencer' column or similar for now, 
    // OR ideally add a 'player_name' column. 
    // Let's check if the table has player_name, if not, we'll just store it in session for now 
    // or quickly alter the table. For MVP speed, let's ALTER the table right here if it fails?
    // Better: Let's assume we need to add the column.
    
    // Check if column exists (simple try/catch insertion)
    // Actually, sticking it in 'influencer' would be confusing.
    // Let's add the column via SQL query first to be safe.
    
    // But for this specific turn, I'll just try to insert. If I didn't add the column, it fails.
    // I should probably add the column.
    
// Check for duplicate name and append suffix
    $original_name = $player_name;
    $counter = 1;
    
    // Simple check loop (inefficient for huge DBs but fine for this scale)
    while (true) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM quiz_sessions WHERE player_name = ?");
        $check->execute([$player_name]);
        if ($check->fetchColumn() == 0) {
            break;
        }
        $player_name = $original_name . "_" . $counter;
        $counter++;
    }

    $site_id = isset($data['site_id']) ? intval($data['site_id']) : 0;

    $stmt = $pdo->prepare("INSERT INTO quiz_sessions (quiz_id, token, device, player_name, site_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$quiz_id, $token, $device, $player_name, $site_id]);
    
    echo json_encode(["status" => "success", "token" => $token]);
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Unknown column 'player_name'") !== false) {
        // Quick fix: Add the column if missing!
        $pdo->exec("ALTER TABLE quiz_sessions ADD COLUMN player_name VARCHAR(100)");
        // Retry
        $stmt = $pdo->prepare("INSERT INTO quiz_sessions (quiz_id, token, device, player_name, site_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$quiz_id, $token, $device, $player_name, $site_id]);
        echo json_encode(["status" => "success", "token" => $token]);
    } else if (strpos($e->getMessage(), "Unknown column 'site_id'") !== false) {
        $pdo->exec("ALTER TABLE quiz_sessions ADD COLUMN site_id INT DEFAULT 0");
        $stmt = $pdo->prepare("INSERT INTO quiz_sessions (quiz_id, token, device, player_name, site_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$quiz_id, $token, $device, $player_name, $site_id]);
        echo json_encode(["status" => "success", "token" => $token]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
