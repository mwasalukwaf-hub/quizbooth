<?php
include 'api/db.php';

try {
    $pdo->beginTransaction();

    // 1. Identify duplicates to delete (keep lowest ID)
    // We want to delete t1 where t1.id > t2.id for same content
    // Let's get the IDs of duplicates first
    $sql = "
        SELECT t1.id 
        FROM quiz_questions t1
        INNER JOIN quiz_questions t2 
        WHERE 
            t1.id > t2.id AND 
            t1.question = t2.question AND 
            t1.language = t2.language AND
            t1.quiz_id = t2.quiz_id
    ";
    
    $stmt = $pdo->query($sql);
    $duplicateIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($duplicateIds)) {
        echo "No duplicates found.\n";
        $pdo->rollBack();
        exit;
    }

    echo "Found duplicate Question IDs: " . implode(", ", $duplicateIds) . "\n";
    
    $inQuery = implode(',', array_fill(0, count($duplicateIds), '?'));

    // 2. Delete options for these questions
    $deleteOptionsSql = "DELETE FROM quiz_options WHERE question_id IN ($inQuery)";
    $stmtOptions = $pdo->prepare($deleteOptionsSql);
    $stmtOptions->execute($duplicateIds);
    echo "Deleted options for duplicate questions.\n";

    // 3. Delete the questions
    $deleteQuestionsSql = "DELETE FROM quiz_questions WHERE id IN ($inQuery)";
    $stmtQuestions = $pdo->prepare($deleteQuestionsSql);
    $stmtQuestions->execute($duplicateIds);
    echo "Deleted duplicate questions.\n";

    $pdo->commit();
    echo "Successfully cleaned up duplicates.";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
