<?php
include 'api/db.php';
try {
    $stmt = $pdo->query("SELECT id, question, quiz_id, language FROM quiz_questions");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Total questions: " . count($rows) . "\n";
    foreach ($rows as $row) {
        echo "ID: " . $row['id'] . ", QuizID: " . $row['quiz_id'] . ", Lang: " . $row['language'] . ", Q: " . $row['question'] . "\n";
    }
} catch (PDOException $e) {
    echo $e->getMessage();
}
