<?php
include __DIR__ . '/../db/connection.php';

$view_final = (isset($_GET['view']) && $_GET['view'] === "final") ? 1 : 0;

$ranking = $conn->query("
    SELECT 
        p.name,
        SUM(
            CASE 
                WHEN r.selected_option = q.correct_option THEN LEAST(10 - r.response_time, 10)
                ELSE 0 
            END
        ) AS total_score
    FROM responses r
    JOIN participants p ON r.participant_id = p.id
    JOIN questions q ON r.question_id = q.id
    WHERE COALESCE(q.is_final, 0) = $view_final
    GROUP BY r.participant_id
    ORDER BY total_score DESC
");

$data = [];
while ($row = $ranking->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
