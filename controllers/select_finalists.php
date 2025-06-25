<?php
include __DIR__ . '/../db/connection.php';

// Clear old
$conn->query("DELETE FROM finalists");

// Get top 3
$ranking = $conn->query("
    SELECT r.participant_id
    FROM responses r
    JOIN questions q ON r.question_id = q.id
    WHERE COALESCE(q.is_final, 0) = 0 AND r.selected_option = q.correct_option
    GROUP BY r.participant_id
    ORDER BY SUM(LEAST(10 - r.response_time, 10)) DESC, AVG(r.response_time) ASC
    LIMIT 3
");

while ($row = $ranking->fetch_assoc()) {
    $pid = $row['participant_id'];
    $conn->query("INSERT INTO finalists (participant_id) VALUES ($pid)");
}

header("Location: ../views/admin.php");
exit;
?>
