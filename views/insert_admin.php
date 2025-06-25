<?php
// run once to insert admin
include __DIR__ . '/../db/connection.php';

$username = 'admin';
$password = password_hash('1234', PASSWORD_DEFAULT); // secure hashed

$stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $password);
$stmt->execute();

echo "Admin created.";
?>
