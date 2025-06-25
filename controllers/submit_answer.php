<?php
session_start();
include __DIR__ . '/../db/connection.php';

if (!isset($_SESSION['participant_id'])) {
    echo "Session expired. Please log in again.";
    exit;
}

$participant_id = $_SESSION['participant_id'];
$question_id = $_POST['question_id'];
$selected_option = $_POST['selected_option'];
$response_time = $_POST['response_time'];

$stmt = $conn->prepare("INSERT INTO responses (participant_id, question_id, selected_option, response_time)
                        VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiid", $participant_id, $question_id, $selected_option, $response_time);

if ($stmt->execute()) {
    echo "Answer recorded!";
} else {
    echo "Failed to save answer.";
}
?>
