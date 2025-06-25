<?php
include __DIR__ . '/../db/connection.php';

// Check if question_id is provided
if (!isset($_POST['question_id']) || empty($_POST['question_id'])) {
    echo "Error: No question ID provided";
    exit;
}

$question_id = (int)$_POST['question_id'];
$now = date("Y-m-d H:i:s");

// Verify the question exists
$question_check = $conn->query("SELECT id FROM questions WHERE id = $question_id");
if ($question_check->num_rows === 0) {
    echo "Error: Question with ID $question_id does not exist";
    exit;
}

// Update game state
$update_result = $conn->query("UPDATE game_state SET current_question_id = $question_id, question_start_time = '$now'");

if ($update_result) {
    // Success - redirect back to moderator panel
    header("Location: ../views/moderator.php");
    exit;
} else {
    echo "Error: Failed to update game state: " . $conn->error;
    exit;
}
// This script updates the game state to start a question and redirects back to the moderator panel.
// It assumes that the game_state table has columns for current_question_id and question_start_time.