<?php
include 'api/db.php';

echo "<h1>Quiz Questions & Options Diagnostic</h1>";

$questions = $pdo->query("SELECT * FROM quiz_questions ORDER BY id")->fetchAll();

echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Question</th><th>Options (ID, Result Key)</th></tr>";

foreach($questions as $q) {
    echo "<tr>";
    echo "<td>" . $q['id'] . "</td>";
    echo "<td>" . htmlspecialchars($q['question']) . "</td>";
    
    $opts = $pdo->prepare("SELECT id, option_text, result_key FROM quiz_options WHERE question_id = ?");
    $opts->execute([$q['id']]);
    $options = $opts->fetchAll();
    
    echo "<td><ul>";
    foreach($options as $o) {
        $color = 'black';
        if($o['result_key'] == 'original') $color = 'blue';
        if($o['result_key'] == 'pineapple') $color = '#ffe600'; // dark yellow
        if($o['result_key'] == 'guarana') $color = 'red';
        
        echo "<li style='color:$color'>[" . $o['id'] . "] " . htmlspecialchars($o['option_text']) . " (" . htmlspecialchars($o['result_key']) . ")</li>";
    }
    echo "</ul></td>";
    echo "</tr>";
}
echo "</table>";
?>
