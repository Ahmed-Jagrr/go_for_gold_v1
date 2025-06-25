<?php
session_start();
include __DIR__ . '/../db/connection.php';

$gs = $conn->query("SELECT session_locked FROM game_state")->fetch_assoc();
if ($gs && $gs['session_locked']) {
    echo "<h2 class='text-center text-red-600 mt-20 text-2xl font-bold'>⛔ A game session is in progress.<br>Please wait for the next round.</h2>";
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);

    if ($name === "") {
        $error = "❗ Name cannot be empty.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM participants WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $counts = $conn->query("SELECT group_id, COUNT(*) AS total FROM participants WHERE group_id IS NOT NULL GROUP BY group_id");
            $group_counts = [1 => 0, 2 => 0, 3 => 0];
            while ($row = $counts->fetch_assoc()) $group_counts[$row['group_id']] = $row['total'];
            asort($group_counts);
            $selected_group = array_key_first($group_counts);

            $stmt = $conn->prepare("INSERT INTO participants (name, group_id) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $selected_group);
            $stmt->execute();
            $participant_id = $stmt->insert_id;
        } else {
            $participant_id = $result->fetch_assoc()['id'];
        }

        $_SESSION['participant_id'] = $participant_id;
        $_SESSION['participant_name'] = $name;
        header("Location: participant.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Going for Gold</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gold-text {
            background: linear-gradient(to right, #facc15, #fbbf24, #facc15);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            animation: shimmer 3s infinite linear;
            background-size: 200% auto;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .bg-glow {
            background: linear-gradient(to right, #1e3a8a, #9333ea);
        }

        @keyframes squeeze {
            0%   { transform: scale(1); }
            50%  { transform: scale(0.9); }
            100% { transform: scale(1); }
        }

        .squeeze {
            animation: squeeze 0.25s ease-in-out;
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
    <audio id="welcomeSound" src="../assets/sounds/welcome.mp3" preload="auto"></audio>
    <audio id="clickSound" src="../assets/sounds/click.mp3" preload="auto"></audio>

    <main class="w-full max-w-lg px-6 sm:px-8">
        <div class="bg-white/10 p-8 rounded-2xl shadow-xl backdrop-blur-lg text-center">
            <h1 class="text-4xl sm:text-5xl font-bold gold-text mb-4 animate-pulse">🏆 Going for Gold</h1>
            <p class="text-yellow-100 mb-6 text-base sm:text-lg">Select your role to begin:</p>

            <!-- Login options -->
            <div class="space-y-4 mb-6">
                <form method="POST" id="joinForm" class="space-y-4">
                    <input type="text" name="name" placeholder="Enter your name" id="nameInput"
                           class="w-full px-4 py-3 rounded-lg text-black focus:outline-none focus:ring-2 focus:ring-yellow-400" required>
                    <button type="submit" id="joinBtn"
                            class="w-full bg-yellow-400 text-black font-bold py-3 rounded-lg hover:bg-yellow-300 transition">
                        🎮 Join as Participant
                    </button>
                </form>

                <a href="moderator_login.php" id="modLogin"
                   class="block w-full text-center bg-blue-500 text-white font-bold py-3 rounded-lg hover:bg-blue-400 transition">
                    🛠️ Login as Moderator
                </a>

                <a href="admin_login.php" id="adminLogin"
                   class="block w-full text-center bg-red-600 text-white font-bold py-3 rounded-lg hover:bg-red-500 transition">
                    🧠 Login as Admin
                </a>
            </div>

            <?php if ($error): ?>
                <p class="text-red-400 text-sm"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        const welcomeSound = document.getElementById("welcomeSound");
        const clickSound = document.getElementById("clickSound");
        const toggleBtn = document.getElementById("soundToggle");
        const joinForm = document.getElementById("joinForm");
        const joinBtn = document.getElementById("joinBtn");
        const nameInput = document.getElementById("nameInput");
        const modLogin = document.getElementById("modLogin");
        const adminLogin = document.getElementById("adminLogin");

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

        joinForm.addEventListener("submit", function(e) {
            const name = nameInput.value.trim();
            if (name === "") {
                e.preventDefault();
                joinBtn.classList.remove("squeeze");
                void joinBtn.offsetWidth;
                joinBtn.classList.add("squeeze");
                return;
            }

            if (!isMuted) clickSound.play();
            welcomeSound.pause();
        });

        [modLogin, adminLogin].forEach(btn => {
            btn.addEventListener("click", e => {
                if (!isMuted) clickSound.play();
                welcomeSound.pause();
            });
        });
    </script>
</body>
</html>
