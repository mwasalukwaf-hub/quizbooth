<?php
include 'api/db.php';
$stmt = $pdo->query("SELECT id, language, question FROM quiz_questions");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
