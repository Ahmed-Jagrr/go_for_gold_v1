<?php
include __DIR__ . '/../db/connection.php';

// Step 1: Check if a gold question is already active
$check = $conn->query("
    SELECT current_question_id 
    FROM game_state 
    WHERE current_question_id IN (SELECT id FROM questions WHERE is_gold = 1)
")->fetch_assoc();

if ($check && $check['current_question_id']) {
    echo "A gold question is already active.";
    exit;
}

// Step 2: Get top score from final round
$top_result = $conn->query("
    SELECT 
        SUM(CASE WHEN r.selected_option = q.correct_option THEN LEAST(10 - r.response_time, 10) ELSE 0 END) AS score
    FROM responses r
    JOIN questions q ON r.question_id = q.id
    WHERE q.is_final = 1
    GROUP BY r.participant_id
    ORDER BY score DESC
    LIMIT 1
")->fetch_assoc();

if (!$top_result) {
    echo "⚠️ No final round data available.";
    exit;
}

$top_score = $top_result['score'];

// Step 3: Find all tied finalists
$tied = $conn->query("
    SELECT r.participant_id
    FROM responses r
    JOIN questions q ON r.question_id = q.id
    WHERE q.is_final = 1
    GROUP BY r.participant_id
    HAVING SUM(CASE WHEN r.selected_option = q.correct_option THEN LEAST(10 - r.response_time, 10) ELSE 0 END) = $top_score
");

if ($tied->num_rows <= 1) {
    echo "✅ No tie among finalists.";
    exit;
}

// Step 4: Save tied finalist IDs in a temporary table
$conn->query("DROP TABLE IF EXISTS tied_finalists");
$conn->query("
    CREATE TABLE tied_finalists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        participant_id INT UNIQUE
    )
");

while ($row = $tied->fetch_assoc()) {
    $pid = $row['participant_id'];
    $conn->query("INSERT INTO tied_finalists (participant_id) VALUES ($pid)");
}

// Step 5: Pick an unused gold question
$used = $conn->query("SELECT DISTINCT question_id FROM responses");
$used_ids = [];
while ($r = $used->fetch_assoc()) {
    $used_ids[] = $r['question_id'];
}
$used_list = implode(',', $used_ids ?: [0]);

$gold = $conn->query("
    SELECT * FROM questions 
    WHERE is_gold = 1 AND id NOT IN ($used_list) 
    ORDER BY RAND() LIMIT 1
");

if ($gold->num_rows === 0) {
    echo "⚠️ No unused gold questions left.";
    exit;
}

$gold_q = $gold->fetch_assoc();
$gold_id = $gold_q['id'];
$now = date("Y-m-d H:i:s");

$conn->query("UPDATE game_state SET current_question_id = $gold_id, question_start_time = '$now'");
header("Location: ../views/admin.php");
exit;
?>
