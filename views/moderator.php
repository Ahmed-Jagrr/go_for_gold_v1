<?php
session_start();
if (!isset($_SESSION['moderator_id'])) {
    header("Location: moderator_login.php");
    exit;
}

include __DIR__ . '/../db/connection.php';



if (isset($_POST['start_session'])) {
    $conn->query("UPDATE game_state SET session_locked = 1");
    $conn->query("TRUNCATE TABLE active_players");
    $conn->query("INSERT INTO active_players (participant_id) SELECT id FROM participants");
    echo "<script>alert('✅ Session started. Only logged-in players can continue.');</script>";
}

// Fetch questions and usage
$questions = $conn->query("SELECT * FROM questions");
$used_q_ids = [];
$used = $conn->query("SELECT DISTINCT question_id FROM responses");
while ($row = $used->fetch_assoc()) {
    $used_q_ids[] = $row['question_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Moderator Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-gradient-to-br from-gray-900 via-indigo-900 to-purple-900 text-white min-h-screen p-6">

    <!-- Click sound -->
    <audio id="clickSound" src="...assests/sounds/click.mp3" preload="auto"></audio>

    <!-- Moderator Info -->
    <div class="flex justify-between items-center mb-4">
        <p class="text-sm sm:text-base text-yellow-300 font-semibold">
            👤 Moderator: <?= htmlspecialchars($_SESSION['moderator_name']) ?>
        </p>
        <form method="POST" action="logout.php">
            <button class="bg-white/20 hover:bg-white/30 text-sm sm:text-base text-white px-4 py-2 rounded">
                🔓 Logout
            </button>
        </form>
    </div>

    <div class="max-w-6xl mx-auto space-y-6">
        <h1 class="text-3xl font-bold text-center text-yellow-300">🎛️ Moderator Control Panel</h1>

        <!-- Session Start -->
        <form method="post" class="text-center">
            <button name="start_session" type="submit" class="bg-red-600 hover:bg-red-500 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition">
                🔒 Start Session
            </button>
        </form>

        <!-- Questions Table -->
        <div class="overflow-x-auto bg-white/10 p-4 rounded-lg backdrop-blur-md shadow-lg">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-white/20 text-yellow-200">
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">Question</th>
                        <th class="px-4 py-2 text-center">Type</th>
                        <th class="px-4 py-2 text-center">Status</th>
                        <th class="px-4 py-2 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($q = $questions->fetch_assoc()):
                    $qid = $q['id'];
                    $type = $q['is_gold'] ? "Gold" : ($q['is_final'] ? "Final" : "Normal");
                    $typeColor = $q['is_gold'] ? "bg-yellow-400 text-black" : ($q['is_final'] ? "bg-blue-500" : "bg-green-500");
                    $used = in_array($qid, $used_q_ids);
                ?>
                    <tr class="<?= $used ? 'opacity-50' : '' ?> border-b border-white/20 hover:bg-white/5">
                        <td class="px-4 py-2"><?= $qid ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($q['question_text']) ?></td>
                        <td class="px-4 py-2 text-center">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold <?= $typeColor ?>">
                                <?= $type ?>
                            </span>
                        </td>
                        <td class="px-4 py-2 text-center"><?= $used ? "🔒 Used" : "✅ Ready" ?></td>
                        <td class="px-4 py-2 text-center">
                            <form method="post" action="../controllers/start_question.php">
                                <input type="hidden" name="question_id" value="<?= $qid ?>">
                                <button
                                    type="submit"
                                    class="px-4 py-2 text-white rounded-md font-bold transition <?= $used ? 'bg-gray-500 opacity-50 cursor-not-allowed' : 'bg-indigo-500 hover:bg-indigo-400' ?>"
                                    <?= $used ? 'disabled' : '' ?>>
                                    Start
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const clickSound = document.getElementById("clickSound");
        function playClickSound(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            clickSound.currentTime = 0;
            clickSound.play();
        }
        document.addEventListener("click", playClickSound);
        document.addEventListener("touchstart", playClickSound);
    </script>

</body>
</html>
