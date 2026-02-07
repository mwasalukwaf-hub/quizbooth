<?php
include 'db.php';

try {
    $sql = file_get_contents(__DIR__ . '/../update_language.sql');
    
    // Split execution because of variables (@q1_id) and multiple statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                // Ignore specific errors like duplicate column or duplicate entry
                if (strpos($e->getMessage(), "Duplicate column") === false && strpos($e->getMessage(), "Duplicate entry") === false) {
                     echo "Statement failed: " . substr($stmt, 0, 50) . "... Error: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    echo "Language update process completed.\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage();
}
