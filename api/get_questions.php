<?php
header('Content-Type: application/json');
include 'db.php';

$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 1;
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';

try {
    // Get questions for the specific language
    // Now filtering by Active Status
    
    $stmt = $pdo->prepare("SELECT id, question FROM quiz_questions WHERE quiz_id = ? AND language = ? AND is_active = 1");
    $stmt->execute([$quiz_id, $lang]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get options
    foreach ($questions as &$question) {
        $optStmt = $pdo->prepare("SELECT id, option_text, result_key FROM quiz_options WHERE question_id = ? AND is_active = 1");
        $optStmt->execute([$question['id']]);
        $question['options'] = $optStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Check if empty (maybe language not found), fallback to English if critical? 
    // User requested strict DB storage, so return empty if not found is correct behavior usually.
    
    echo json_encode($questions);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
