<?php
header('Content-Type: application/json');
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['token']) || !isset($data['question_id']) || !isset($data['option_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

try {
    // Get result key for the chosen option
    $stmt = $pdo->prepare("SELECT result_key FROM quiz_options WHERE id = ?");
    $stmt->execute([$data['option_id']]);
    $result_key = $stmt->fetchColumn();

    if ($result_key) {
        // Record answer
        $insert = $pdo->prepare("INSERT INTO quiz_answers (session_token, question_id, option_id, result_key) VALUES (?, ?, ?, ?)");
        $insert->execute([$data['token'], $data['question_id'], $data['option_id'], $result_key]);
        echo json_encode(["status" => "success"]);
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid option"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
