<?php
session_start();
include __DIR__ . '/../db/connection.php';

// Debug: Log what's happening
error_log("fetch_active_question.php called");

if (!isset($_SESSION['participant_id'])) {
    error_log("No participant_id in session");
    echo "not_allowed";
    exit;
}

$participant_id = $_SESSION['participant_id'];
error_log("Participant ID: " . $participant_id);

// Get game state
$gs_result = $conn->query("SELECT * FROM game_state LIMIT 1");
if (!$gs_result) {
    error_log("Database error getting game state");
    echo "none"; // Database error
    exit;
}

$gs = $gs_result->fetch_assoc();
error_log("Game state: " . print_r($gs, true));

if (!$gs || !$gs['current_question_id']) {
    error_log("No current question ID");
    echo "none";
    exit;
}

$qid = $gs['current_question_id'];
error_log("Question ID: " . $qid);

// Get question details
$q_result = $conn->query("SELECT * FROM questions WHERE id = $qid");
if (!$q_result) {
    error_log("Database error getting question");
    echo "none"; // Database error
    exit;
}

$q = $q_result->fetch_assoc();
if (!$q) {
    error_log("Question not found for ID: " . $qid);
    echo "none";
    exit;
}

error_log("Question found: " . print_r($q, true));

// Check if participant already answered
$check = $conn->query("SELECT * FROM responses WHERE participant_id = $participant_id AND question_id = $qid");
if ($check->num_rows > 0) {
    error_log("Participant already answered this question");
    echo "none";
    exit;
}

// Final round access
if ($q['is_final']) {
    $allowed = $conn->query("SELECT * FROM finalists WHERE participant_id = $participant_id");
    if ($allowed->num_rows === 0) {
        error_log("Participant not allowed for final round");
        echo "not_allowed";
        exit;
    }
}

// Gold question access
if ($q['is_gold']) {
    $allowed = $conn->query("SELECT * FROM tied_finalists WHERE participant_id = $participant_id");
    if ($allowed->num_rows === 0) {
        error_log("Participant not allowed for gold question");
        echo "not_allowed";
        exit;
    }
}

error_log("Returning question data");
echo json_encode($q);
?>
