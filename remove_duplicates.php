<?php
include 'api/db.php';
// Delete options first to avoid FK constraint violation
$pdo->exec("DELETE FROM quiz_options WHERE question_id > 12");
$pdo->exec("DELETE FROM quiz_questions WHERE id > 12");
echo "Duplicates removed.";
