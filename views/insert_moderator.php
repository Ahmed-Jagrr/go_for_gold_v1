<?php
include __DIR__ . '/../db/connection.php';

$username = 'moderator';
$password = password_hash('1234', PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO moderators (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $password);
$stmt->execute();

echo "Moderator inserted.";
