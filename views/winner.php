<?php
include __DIR__ . '/../db/connection.php';

$winners = $conn->query("
    SELECT 
        p.name,
        SUM(
            CASE 
                WHEN r.selected_option = q.correct_option THEN LEAST(10 - r.response_time, 10)
                ELSE 0 
            END
        ) AS score
    FROM responses r
    JOIN participants p ON r.participant_id = p.id
    JOIN questions q ON r.question_id = q.id
    GROUP BY p.id
    ORDER BY score DESC
    LIMIT 3
");

$top = [];
while ($row = $winners->fetch_assoc()) {
    if (!empty($row['name'])) {
        $top[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏆 Live Podium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: radial-gradient(circle at center, #facc15, #9333ea);
            overflow: hidden;
        }
        .tier {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            padding: 1rem;
            border-radius: 1rem 1rem 0 0;
            box-shadow: 0 5px 10px #00000055;
            text-align: center;
            color: white;
            width: 110px;
        }
        .gold { background-color: #ffd700; color: #000; height: 220px; }
        .silver { background-color: #c0c0c0; color: #000; height: 180px; }
        .bronze { background-color: #cd7f32; color: #000; height: 150px; }
        
        /* Mobile adjustments */
        @media (max-width: 640px) {
            .tier {
                width: 90px;
                padding: 0.75rem;
            }
            .gold { height: 180px; }
            .silver { height: 150px; }
            .bronze { height: 120px; }
            .podium-container {
                gap: 0.5rem !important;
            }
            h1 {
                font-size: 2rem !important;
                margin-top: 1rem !important;
            }
            .reset-btn {
                padding: 0.75rem 1.5rem !important;
                font-size: 0.9rem !important;
            }
        }
    </style>
</head>
<body class="flex flex-col items-center justify-center text-center min-h-screen text-white space-y-6 sm:space-y-10 px-4">

    <!-- Winner Sound (muted initially, then unmuted and played) -->
    <audio id="winnerSound" src="../assets/sounds/winner.mp3" muted></audio>

    <h1 class="text-4xl font-bold animate-pulse mt-4 sm:mt-8">🏆 Podium</h1>

    <?php if (count($top) >= 1): ?>
    <div class="flex justify-center items-end gap-2 sm:gap-4 mt-4 sm:mt-6 podium-container">
        <!-- 🥈 2nd Place -->
        <?php if (isset($top[1])): ?>
        <div class="tier silver">
            <div class="text-lg sm:text-xl font-bold mb-1 sm:mb-2">🥈 <?= htmlspecialchars($top[1]['name']) ?></div>
            <div class="text-xs sm:text-sm"><?= round($top[1]['score'], 2) ?> pts</div>
        </div>
        <?php endif; ?>

        <!-- 🥇 1st Place -->
        <div class="tier gold scale-105 sm:scale-110">
            <div class="text-xl sm:text-2xl font-black mb-1 sm:mb-2">🥇 <?= htmlspecialchars($top[0]['name']) ?></div>
            <div class="text-xs sm:text-sm"><?= round($top[0]['score'], 2) ?> pts</div>
        </div>

        <!-- 🥉 3rd Place -->
        <?php if (isset($top[2])): ?>
        <div class="tier bronze">
            <div class="text-lg sm:text-xl font-bold mb-1 sm:mb-2">🥉 <?= htmlspecialchars($top[2]['name']) ?></div>
            <div class="text-xs sm:text-sm"><?= round($top[2]['score'], 2) ?> pts</div>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
        <p class="text-white text-lg sm:text-xl mt-6 sm:mt-10">❗ No participant data available.</p>
    <?php endif; ?>

    <a href="../controllers/reset_game.php" class="mt-6 sm:mt-10 bg-black/50 text-white px-4 py-2 sm:px-6 sm:py-3 rounded-lg hover:bg-black/70 font-bold reset-btn">
        🔄 Reset Game
    </a>

    <!-- JavaScript to unmute and autoplay sound -->
    <script>
        window.onload = () => {
            const audio = document.getElementById('winnerSound');
            audio.muted = false;
            audio.play().catch((e) => {
                console.log("Autoplay failed, trying again after user click");
                document.addEventListener("click", () => audio.play(), { once: true });
            });
        };
    </script>
</body>
</html>