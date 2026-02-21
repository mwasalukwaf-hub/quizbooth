<?php
header('Content-Type: application/json');
include 'db.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';

if (!$token) {
    http_response_code(400);
    echo json_encode(["error" => "Token required"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT result_key, COUNT(*) as total
        FROM quiz_answers
        WHERE session_token = ?
        GROUP BY result_key
        ORDER BY total DESC, RAND()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $resultKey = $stmt->fetchColumn();

    if (!$resultKey) $resultKey = 'original';

    // 2. Save result to session used
    $update = $pdo->prepare("UPDATE quiz_sessions SET result_key = ? WHERE token = ?");
    $update->execute([$resultKey, $token]);

    // 3. Fetch Translated Content for the Result
    $contentStmt = $pdo->prepare("SELECT title, description, cta FROM quiz_results_content WHERE result_key = ? AND language = ? LIMIT 1");
    $contentStmt->execute([$resultKey, $lang]);
    $content = $contentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$content) {
        // Fallback to English if translation missing
        $contentStmt->execute([$resultKey, 'en']);
        $content = $contentStmt->fetch(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        "result" => $resultKey,
        "content" => $content
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
