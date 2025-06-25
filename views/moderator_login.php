<?php
session_start();
include __DIR__ . '/../db/connection.php';

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM moderators WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $mod = $res->fetch_assoc();
        if (password_verify($password, $mod['password'])) {
            $_SESSION['moderator_id'] = $mod['id'];
            $_SESSION['moderator_name'] = $username;
            header("Location: moderator.php");
            exit;
        }
    }

    $error = "❌ Invalid username or password.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Moderator Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-glow {
            background: linear-gradient(to right, #1e3a8a, #9333ea);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-glow text-white font-sans">

    <!-- Sound toggle -->
    <div class="fixed top-4 right-4 z-50">
        <button id="soundToggle" class="text-yellow-300 bg-black/30 p-2 rounded-full hover:bg-black/50 transition text-lg">
            🔊
        </button>
    </div>

    <!-- Audio -->
    <audio id="welcomeSound" src="../assets/sounds/welcome.mp3" preload="auto" loop></audio>
    <audio id="clickSound" src="../assets/sounds/click.mp3" preload="auto"></audio>

    <div class="bg-white/10 p-8 rounded-xl shadow-lg backdrop-blur-lg max-w-md w-full text-center">
        <h1 class="text-3xl font-bold mb-4">🛠️ Moderator Login</h1>

        <form method="POST" id="loginForm" class="space-y-4">
            <input type="text" name="username" placeholder="Username"
                   class="w-full px-4 py-2 rounded-lg text-black" required>
            <input type="password" name="password" placeholder="Password"
                   class="w-full px-4 py-2 rounded-lg text-black" required>
            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 rounded-lg">
                Login
            </button>
        </form>

        <?php if ($error): ?>
            <p class="text-red-300 mt-4"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </div>

    <script>
        const welcomeSound = document.getElementById("welcomeSound");
        const clickSound = document.getElementById("clickSound");
        const toggleBtn = document.getElementById("soundToggle");
        let isMuted = false;

        window.addEventListener("load", () => {
            if (!isMuted) {
                welcomeSound.play().catch(() => {});
            }
        });

        toggleBtn.addEventListener("click", () => {
            isMuted = !isMuted;
            toggleBtn.textContent = isMuted ? "🔇" : "🔊";
            if (isMuted) {
                welcomeSound.pause();
            } else {
                welcomeSound.play().catch(() => {});
            }
        });

        document.getElementById("loginForm").addEventListener("submit", function(e) {
            if (!isMuted) clickSound.play();
            welcomeSound.pause();
        });
    </script>
</body>
</html>
