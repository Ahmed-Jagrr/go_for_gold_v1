<?php
session_start();
include __DIR__ . '/../db/connection.php';

if (!isset($_SESSION['participant_id'])) {
    header("Location: index.php");
    exit;
}

$participant_id = $_SESSION['participant_id'];
$participant_name = $_SESSION['participant_name'];

$result = $conn->query("SELECT group_id FROM participants WHERE id = $participant_id");
$group_id = $result->fetch_assoc()['group_id'];
$group_name = ['A', 'B', 'C'][$group_id - 1] ?? 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play — Going for Gold</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .timer-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: conic-gradient(#facc15 calc(var(--value) * 1%), #ddd 0%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            color: #1f2937;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .animate-shake {
            animation: shake 0.3s ease-in-out;
        }
        /* Mobile-specific adjustments */
        @media (max-width: 640px) {
            .option-text {
                font-size: 1rem;
                padding: 1rem 0.5rem;
            }
            .timer-circle {
                width: 50px;
                height: 50px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-r from-purple-800 to-indigo-900 text-white min-h-screen flex items-center justify-center px-4">

<!-- Sound elements -->
<audio id="tickSound" src="../assets/sounds/tick.mp3" preload="auto"></audio>
<audio id="clickSound" src="../assets/sounds/click.mp3" preload="auto"></audio>
<audio id="logoutSound" src="../assets/sounds/logout.mp3" preload="auto"></audio>

<div class="max-w-xl w-full text-center space-y-6 mx-4">
    <p class="text-sm text-yellow-300">👤 <?= htmlspecialchars($participant_name) ?> — Group <?= $group_name ?></p>
    <h2 id="titleMessage" class="text-2xl font-bold">Waiting for the moderator to start a question...</h2>

    <div id="questionArea" class="hidden bg-white/10 p-4 sm:p-6 rounded-xl shadow-lg backdrop-blur-md space-y-4">
        <div class="flex items-center justify-between mb-2">
            <p id="question" class="text-lg font-semibold text-white text-left"></p>
            <div class="timer-circle" id="timer" style="--value: 100;">10</div>
        </div>

        <div id="options" class="grid grid-cols-2 gap-3 sm:gap-4"></div>
        <p id="status" class="font-bold mt-4 text-green-400"></p>
    </div>

    <a href="logout.php" id="logoutLink" class="text-sm text-yellow-200 underline">Logout</a>
</div>

<script>
    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    let currentQuestionId = null;
    let answered = false;
    let timerInterval;
    let tickInterval;
    let startTime;

    const tickSound = document.getElementById("tickSound");
    const clickSound = document.getElementById("clickSound");
    const logoutSound = document.getElementById("logoutSound");

    function updateTimerDisplay(value) {
        const t = document.getElementById("timer");
        t.textContent = value;
        t.style.setProperty('--value', (value / 10) * 100);
    }

    let ticking = false;

    function startTicking() {
        if (ticking) return;
        ticking = true;

        tickSound.currentTime = 0;
        tickSound.play();

        tickSound.onended = () => {
            if (ticking) {
                tickSound.currentTime = 0;
                tickSound.play();
            }
        };
    }

    function stopTicking() {
        ticking = false;
        tickSound.pause();
        tickSound.currentTime = 0;
        tickSound.onended = null;
    }

    function startTimer() {
        let timeLeft = 10;
        updateTimerDisplay(timeLeft);
        startTicking();

        timerInterval = setInterval(() => {
            timeLeft--;
            updateTimerDisplay(timeLeft);
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                stopTicking();
                if (!answered) {
                    document.getElementById("status").textContent = "⏰ Time's up!";
                    document.querySelectorAll(".option").forEach(btn => btn.disabled = true);
                }
            }
        }, 1000);
    }

    function loadQuestion() {
        console.log("🔍 Fetching active question...");
        fetch("../controllers/fetch_active_question.php")
            .then(res => {
                console.log("📡 Response status:", res.status);
                return res.text();
            })
            .then(data => {
                console.log("📄 Raw response data:", data);
                
                if (!data || data === "none") {
                    console.log("❌ No active question or data is empty");
                    return;
                }

                if (data === "not_allowed") {
                    console.log("⛔ Participant not allowed");
                    document.getElementById("titleMessage").textContent = "⛔ You are not allowed to participate in this round.";
                    document.getElementById("questionArea").classList.add("hidden");
                    return;
                }

                try {
                    let q = JSON.parse(data);
                    console.log("✅ Parsed question data:", q);

                    if (q.id !== currentQuestionId) {
                        currentQuestionId = q.id;
                        answered = false;

                        document.getElementById("titleMessage").textContent = q.is_final == 1
                            ? "🏁 Final Round Question"
                            : (q.is_gold == 1 ? "🥇 Tie-breaker Gold Question" : "📘 Main Round Question");

                        document.getElementById("question").textContent = q.question_text;
                        let optsHtml = '';
                        const colors = ['bg-red-500', 'bg-blue-500', 'bg-yellow-400', 'bg-green-500'];
                        for (let i = 1; i <= 4; i++) {
                            const optionText = escapeHtml(q['option' + i]).replace(/&lt;br&gt;/g, '<br>').replace(/\\n/g, '<br>');
                            optsHtml += `
                                <button class="option ${colors[i - 1]} text-white p-3 sm:p-4 rounded-lg font-semibold hover:opacity-90 transition w-full" data-opt="${i}">
                                    <span class="text-white block option-text" style="white-space:pre-line;">${optionText}</span>
                                </button>`;
                        }
                        document.getElementById("options").innerHTML = optsHtml;
                        document.getElementById("status").textContent = "";
                        document.getElementById("questionArea").classList.remove("hidden");
                        startTime = Date.now();
                        startTimer();
                        console.log("🎯 Question displayed successfully!");
                    }
                } catch (error) {
                    console.error("❌ Error parsing JSON:", error);
                    console.error("Raw data that failed to parse:", data);
                }
            })
            .catch(error => {
                console.error("❌ Fetch error:", error);
            });
    }

    setInterval(loadQuestion, 2000);

    document.addEventListener("click", function (e) {
        if (!clickSound.paused) clickSound.pause();
        clickSound.currentTime = 0;
        clickSound.play();

        if (e.target.closest(".option") && !answered) {
            answered = true;
            clearInterval(timerInterval);
            stopTicking();

            const selectedBtn = e.target.closest(".option");
            const selected = selectedBtn.getAttribute("data-opt");
            const responseTime = (Date.now() - startTime) / 1000;

            fetch("../controllers/submit_answer.php", {
                method: "POST",
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    question_id: currentQuestionId,
                    selected_option: selected,
                    response_time: responseTime
                })
            })
                .then(res => res.text())
                .then(data => {
                    document.getElementById("status").textContent = data;

                    if (data.toLowerCase().includes("correct")) {
                        selectedBtn.classList.add("bg-green-500", "ring-4", "ring-green-300", "scale-105");
                    } else {
                        selectedBtn.classList.add("border-4", "border-red-500", "animate-shake");
                    }

                    document.querySelectorAll(".option").forEach(btn => {
                        btn.disabled = true;
                        btn.classList.add("opacity-60", "cursor-not-allowed");
                    });

                    setTimeout(() => {
                        document.getElementById("questionArea").classList.add("hidden");
                    }, 2000);
                });
        }
    });

    // Logout sound + quick redirect (~0.1 sec)
    const logoutLink = document.getElementById('logoutLink');

    logoutLink.addEventListener('click', function(e) {
        e.preventDefault(); // prevent immediate navigation

        logoutSound.currentTime = 0;
        logoutSound.play();

        setTimeout(() => {
            window.location.href = logoutLink.href;
        }, 100); // 100 milliseconds = 0.1 seconds
    });
</script>
</body>
</html>