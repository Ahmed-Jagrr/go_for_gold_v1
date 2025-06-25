<?php
include __DIR__ . '/../db/connection.php';

$conn->query("DELETE FROM responses");
$conn->query("DELETE FROM finalists");
$conn->query("DROP TABLE IF EXISTS tied_finalists");
$conn->query("UPDATE game_state SET current_question_id = NULL, question_start_time = NULL, session_locked = 0");
$conn->query("TRUNCATE TABLE active_players");

echo "<script>alert('✅ Game reset. New participants may now register.'); window.location.href='../views/admin.php';</script>";
?>
