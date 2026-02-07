<?php
include 'db.php';

try {
    // Read the SQL file
    $sql = file_get_contents('../setup.sql');

    // Remove the "USE database" line since we are already connected or might not have rights to switch context in some setups,
    // although with root it's fine. But let's rely on the connection.
    // However, the setup.sql creates the DB if not exists.
    // Let's just execute the commands.
    
    // PDO doesn't support multiple statements in one exec call by default in some configurations,
    // but in MySQL it often does if MYSQL_ATTR_MULTI_STATEMENTS is on.
    // Let's try splitting them to be safe and provide better feedback.
    
    // Simple split by semicolon/newline combo is often enough for simple dumps
    // detailed parsing is hard, but for this specific file it should work.
    
    // Actually, let's just try running it raw first, usually works for import scripts if the driver allows.
    // If not, we fall back to splitting.
    
    echo "<h1>Installing Tables...</h1>";
    
    $pdo->exec($sql);
    
    echo "<p style='color:green'>Success! Tables created and data seeded.</p>";
    echo "<a href='../admin/dashboard.php'>Go to Dashboard</a><br>";
    echo "<a href='../index.html'>Go to Quiz</a>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>Error</h2>";
    echo $e->getMessage();
    echo "<br><br><b>Attempting to run statement by statement...</b><br>";
    
    // Fallback: simple split
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
                echo "Executed: " . substr($stmt, 0, 50) . "...<br>";
            } catch (PDOException $ex) {
                // Ignore "USE" error if it fails
                if (stripos($stmt, 'USE') !== false) continue;
                echo "<span style='color:red'>Failed: " . substr($stmt, 0, 50) . "... (" . $ex->getMessage() . ")</span><br>";
            }
        }
    }
    echo "<p>Done trying fallback installation.</p>";
}
