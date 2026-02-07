<?php
include 'api/db.php';

try {
    // Find duplicates
    $sql = "
        DELETE t1 FROM quiz_questions t1
        INNER JOIN quiz_questions t2 
        WHERE 
            t1.id > t2.id AND 
            t1.question = t2.question AND 
            t1.language = t2.language AND
            t1.quiz_id = t2.quiz_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    echo "Deleted " . $stmt->rowCount() . " duplicate questions.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
