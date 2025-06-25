<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include __DIR__ . '/../db/connection.php';


function getGroupRanking($group_id) {
    global $conn;
    $query = "
        SELECT 
            p.name,
            SUM(
                CASE 
                    WHEN r.selected_option = q.correct_option THEN LEAST(10 - r.response_time, 10)
                    ELSE 0 
                END
            ) AS total_score,
            SUM(r.selected_option = q.correct_option) AS correct_answers,
            ROUND(AVG(r.response_time), 2) AS avg_time
        FROM responses r
        JOIN participants p ON r.participant_id = p.id
        JOIN questions q ON r.question_id = q.id
        WHERE p.group_id = $group_id AND COALESCE(q.is_final, 0) = 0
        GROUP BY r.participant_id
        ORDER BY total_score DESC, avg_time ASC
    ";
    return $conn->query($query);
}

function getFinalistsRanking() {
    global $conn;
    $query = "
        SELECT 
            p.name,
            p.group_id,
            SUM(
                CASE 
                    WHEN r.selected_option = q.correct_option THEN LEAST(10 - r.response_time, 10)
                    ELSE 0 
                END
            ) AS total_score,
            SUM(r.selected_option = q.correct_option) AS correct_answers,
            ROUND(AVG(r.response_time), 2) AS avg_time
        FROM responses r
        JOIN participants p ON r.participant_id = p.id
        JOIN questions q ON r.question_id = q.id
        JOIN finalists f ON p.id = f.participant_id
        WHERE COALESCE(q.is_final, 0) = 1
        GROUP BY r.participant_id
        ORDER BY total_score DESC, avg_time ASC
    ";
    return $conn->query($query);
}

// Check if finalists exist
$finalists_count = $conn->query("SELECT COUNT(*) as count FROM finalists")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        html, body { height: 100%; overflow-y: auto; }
        .dashboard-container { min-height: 100vh; display: flex; flex-direction: column; }
        .content-wrap { flex: 1; }
        .tab-button { transition: all 0.3s ease; }
        .tab-button.active { background-color: rgba(255, 215, 0, 0.3); border-color: rgb(255, 215, 0); }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-purple-900 to-indigo-800 text-white">
    <div class="dashboard-container">
        <div class="flex justify-between items-center px-4 pt-4 sm:px-6">
            <p class="text-sm sm:text-base text-yellow-300 font-semibold">
                👤 Logged in as: <?= htmlspecialchars($_SESSION['admin_name']) ?>
            </p>
            <form method="POST" action="../views/logout.php">
                <button class="bg-white/20 hover:bg-white/30 text-sm sm:text-base text-white px-4 py-2 rounded">
                    🔓 Logout
                </button>
            </form>
        </div>

        <div class="content-wrap p-4 sm:p-6">
            <div class="max-w-6xl mx-auto space-y-6 sm:space-y-8">
                <h1 class="text-2xl sm:text-4xl font-bold text-center text-yellow-300">🏆 Admin Dashboard</h1>

                <!-- Tab Navigation -->
                <div class="flex justify-center space-x-2 sm:space-x-4">
                    <button id="mainTab" class="tab-button active bg-white/20 hover:bg-white/30 text-white font-bold py-2 px-4 sm:py-3 sm:px-6 rounded-lg text-sm sm:text-base border-2 border-transparent">
                        📊 Main Round
                    </button>
                    <button id="finalTab" class="tab-button bg-white/20 hover:bg-white/30 text-white font-bold py-2 px-4 sm:py-3 sm:px-6 rounded-lg text-sm sm:text-base border-2 border-transparent">
                        🏁 Final Round
                    </button>
                </div>

                <!-- Main Round Dashboard -->
                <div id="mainDashboard" class="dashboard-section">
                    <h2 class="text-xl sm:text-2xl font-bold text-center text-yellow-300 mb-6">📊 Main Round Rankings</h2>

                    <!-- Group Rankings -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                        <?php foreach ([1 => 'A', 2 => 'B', 3 => 'C'] as $gid => $label):
                            $data = getGroupRanking($gid);
                        ?>
                        <div class="bg-white/10 p-3 sm:p-4 rounded-lg backdrop-blur-md shadow-lg">
                            <h3 class="text-lg sm:text-xl font-semibold text-center mb-2">Group <?= $label ?></h3>
                            <?php if ($data->num_rows === 0): ?>
                                <p class="text-center text-gray-300">No data.</p>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs sm:text-sm text-white">
                                        <thead>
                                            <tr class="text-left border-b border-white/20">
                                                <th class="p-1 sm:p-2">#</th>
                                                <th class="p-1 sm:p-2">Name</th>
                                                <th class="p-1 sm:p-2">Score</th>
                                                <th class="p-1 sm:p-2">Correct</th>
                                                <th class="p-1 sm:p-2">Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php $i = 1; while($row = $data->fetch_assoc()): ?>
                                            <tr class="border-b border-white/20">
                                                <td class="p-1 sm:p-2"><?= $i++ ?></td>
                                                <td class="p-1 sm:p-2"><?= htmlspecialchars($row['name']) ?></td>
                                                <td class="p-1 sm:p-2"><?= round($row['total_score'], 2) ?></td>
                                                <td class="p-1 sm:p-2"><?= $row['correct_answers'] ?></td>
                                                <td class="p-1 sm:p-2"><?= $row['avg_time'] ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Main Round Score Chart -->
                    <div class="bg-white/10 p-4 sm:p-6 rounded-xl shadow-lg mt-6 sm:mt-8">
                        <h3 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4 text-center">📈 Main Round Score Chart</h3>
                        <div class="chart-container" style="position: relative; height:200px;">
                            <canvas id="mainScoreChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Final Round Dashboard -->
                <div id="finalDashboard" class="dashboard-section hidden">
                    <h2 class="text-xl sm:text-2xl font-bold text-center text-yellow-300 mb-6">🏁 Final Round Rankings</h2>

                    <?php if ($finalists_count > 0): ?>
                        <!-- Finalists Rankings -->
                        <div class="bg-white/10 p-4 sm:p-6 rounded-lg backdrop-blur-md shadow-lg">
                            <h3 class="text-lg sm:text-xl font-semibold text-center mb-4">🏆 Finalists Performance</h3>
                            <?php 
                            $finalists_data = getFinalistsRanking();
                            if ($finalists_data->num_rows === 0): ?>
                                <p class="text-center text-gray-300">No final round data yet.</p>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm sm:text-base text-white">
                                        <thead>
                                            <tr class="text-left border-b border-white/20">
                                                <th class="p-2 sm:p-3">#</th>
                                                <th class="p-2 sm:p-3">Name</th>
                                                <th class="p-2 sm:p-3">Group</th>
                                                <th class="p-2 sm:p-3">Score</th>
                                                <th class="p-2 sm:p-3">Correct</th>
                                                <th class="p-2 sm:p-3">Avg Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php $i = 1; while($row = $finalists_data->fetch_assoc()): ?>
                                            <tr class="border-b border-white/20 hover:bg-white/5">
                                                <td class="p-2 sm:p-3 font-bold"><?= $i++ ?></td>
                                                <td class="p-2 sm:p-3"><?= htmlspecialchars($row['name']) ?></td>
                                                <td class="p-2 sm:p-3"><?= ['A', 'B', 'C'][$row['group_id'] - 1] ?? 'N/A' ?></td>
                                                <td class="p-2 sm:p-3 font-bold text-yellow-300"><?= round($row['total_score'], 2) ?></td>
                                                <td class="p-2 sm:p-3"><?= $row['correct_answers'] ?></td>
                                                <td class="p-2 sm:p-3"><?= $row['avg_time'] ?>s</td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Final Round Score Chart -->
                        <div class="bg-white/10 p-4 sm:p-6 rounded-xl shadow-lg mt-6 sm:mt-8">
                            <h3 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4 text-center">📈 Final Round Score Chart</h3>
                            <div class="chart-container" style="position: relative; height:200px;">
                                <canvas id="finalScoreChart"></canvas>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-white/10 p-6 rounded-lg backdrop-blur-md shadow-lg text-center">
                            <p class="text-lg text-gray-300 mb-4">No finalists selected yet.</p>
                            <p class="text-sm text-gray-400">Use the "Select Finalists" button to choose the top 3 participants for the final round.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Control Buttons -->
                <div class="flex flex-wrap justify-center gap-2 sm:gap-4 mt-4 sm:mt-6">
                    <form method='post' action='../controllers/select_finalists.php'>
                        <button class="bg-green-500 hover:bg-green-400 text-black font-bold py-2 px-4 sm:py-3 sm:px-6 rounded-lg text-sm sm:text-base">🏁 Select Finalists</button>
                    </form>
                    <form method='post' action='../controllers/trigger_gold.php'>
                        <button class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold py-2 px-4 sm:py-3 sm:px-6 rounded-lg text-sm sm:text-base">🥇 Trigger Gold</button>
                    </form>
                    <form method='post' action='../controllers/reset_game.php' onsubmit="return confirm('Are you sure you want to reset?');">
                        <button class="bg-red-600 hover:bg-red-500 text-white font-bold py-2 px-4 sm:py-3 sm:px-6 rounded-lg text-sm sm:text-base">🔁 Reset</button>
                    </form>
                    <form method='get' action='winner.php'>
                        <button class="bg-yellow-500 hover:bg-yellow-400 text-black font-bold py-2 px-4 sm:py-3 sm:px-6 rounded-lg text-sm sm:text-base">🏆 Winner</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    let mainChart, finalChart;
    
    function fetchMainData() {
        fetch("get_ranking_data.php?view=main")
            .then(res => res.json())
            .then(data => {
                const names = data.map(d => d.name);
                const scores = data.map(d => parseFloat(d.total_score));

                if (!mainChart) {
                    const ctx = document.getElementById('mainScoreChart').getContext('2d');
                    mainChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: names,
                            datasets: [{
                                label: 'Main Round Score',
                                data: scores,
                                backgroundColor: 'rgba(255, 215, 0, 0.7)',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, ticks: { font: { size: 10 }}},
                                x: { ticks: { font: { size: 10 }}}
                            },
                            plugins: {
                                legend: { labels: { font: { size: 12 }}}
                            }
                        }
                    });
                } else {
                    mainChart.data.labels = names;
                    mainChart.data.datasets[0].data = scores;
                    mainChart.update();
                }
            });
    }

    function fetchFinalData() {
        fetch("get_ranking_data.php?view=final")
            .then(res => res.json())
            .then(data => {
                const names = data.map(d => d.name);
                const scores = data.map(d => parseFloat(d.total_score));

                if (!finalChart) {
                    const ctx = document.getElementById('finalScoreChart').getContext('2d');
                    finalChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: names,
                            datasets: [{
                                label: 'Final Round Score',
                                data: scores,
                                backgroundColor: 'rgba(255, 0, 0, 0.7)',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, ticks: { font: { size: 10 }}},
                                x: { ticks: { font: { size: 10 }}}
                            },
                            plugins: {
                                legend: { labels: { font: { size: 12 }}}
                            }
                        }
                    });
                } else {
                    finalChart.data.labels = names;
                    finalChart.data.datasets[0].data = scores;
                    finalChart.update();
                }
            });
    }

    // Tab switching functionality
    document.getElementById('mainTab').addEventListener('click', function() {
        document.getElementById('mainDashboard').classList.remove('hidden');
        document.getElementById('finalDashboard').classList.add('hidden');
        document.getElementById('mainTab').classList.add('active');
        document.getElementById('finalTab').classList.remove('active');
        fetchMainData();
    });

    document.getElementById('finalTab').addEventListener('click', function() {
        document.getElementById('mainDashboard').classList.add('hidden');
        document.getElementById('finalDashboard').classList.remove('hidden');
        document.getElementById('mainTab').classList.remove('active');
        document.getElementById('finalTab').classList.add('active');
        fetchFinalData();
    });

    // Initial data load
    fetchMainData();
    const dataRefresh = setInterval(() => {
        if (document.getElementById('mainDashboard').classList.contains('hidden')) {
            fetchFinalData();
        } else {
            fetchMainData();
        }
    }, 5000);
    
    window.addEventListener('beforeunload', () => clearInterval(dataRefresh));
    </script>
</body>
</html>
