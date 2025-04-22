<?php
// ============================
// FILE: games.php
// ============================
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$initials = strtoupper(substr($user['username'], 0, 1));

// Fetch daily earnings
$earningStmt = $pdo->prepare("SELECT SUM(reward) AS today_earnings FROM quiz_attempts WHERE user_id = ? AND DATE(created_at) = CURDATE()");
$earningStmt->execute([$user_id]);
$earnings = $earningStmt->fetch()['today_earnings'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | Answer & Earn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h2>LOOMA</h2>
            <p>Earn While You Play</p>
        </div>
        <nav class="nav flex-column">
            <a href="index1.php" class="nav-link"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="games.php" class="nav-link active"><i class="fas fa-gamepad"></i><span>Games</span></a>
            <a href="wallet1.php" class="nav-link"><i class="fas fa-chart-line"></i><span>Earnings</span></a>
            <a href="referrals.php" class="nav-link"><i class="fas fa-users"></i><span>Referrals</span></a>
            <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i><span>Log out</span></a>
        </nav>
        <div class="sidebar-footer">
            <p>© 2025 Looma</p>
        </div>
    </div>

    <div class="main-content" id="mainContent">
        <div class="top-navbar">
            <h2>LOOMA</h2>
            <div class="user-profile">
                <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div><div class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></div></div>
            </div>
        </div>

        <section class="game-section py-4 animate-fadeIn">
            <div class="container">
                <h2>Answer quizzes and earn</h2>
                <h5 class="text-success">You've earned: <?php echo $earnings; ?> coins today!</h5>
                <h3>Daily quizzes</h3>
                <div id="quiz-container" class="mt-4">
                    <div class="card p-4">
                        <h4 id="question-text">Loading question...</h4>
                        <div id="spinner" class="text-center my-3" style="display: none;">
                            <div class="spinner-border text-primary"></div>
                        </div>
                        <div id="answers" class="mt-3"></div>
                        <div id="feedback" class="mt-3 fw-bold"></div>
                        <a href="leaderboard.php" class="btn btn-outline-warning mt-3">🏆 View Leaderboard</a>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div>
        <a href="games.php" class="btn btn-primary" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">More Games</a>
    </div>

    <div class="mobile-bottom-nav">
        <a href="index1.php" class="mobile-nav-item"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="games.php" class="mobile-nav-item active"><i class="fas fa-gamepad"></i><span>Games</span></a>
        <a href="wallet1.php" class="mobile-nav-item"><i class="fas fa-wallet"></i><span>Earnings</span></a>
        <a href="referrals.php" class="mobile-nav-item"><i class="fas fa-users"></i><span>Refer</span></a>
        <a href="settings.php" class="mobile-nav-item"><i class="fas fa-user"></i><span>Account</span></a>
        <a href="logout.php" class="mobile-nav-item"><i class="fas fa-sign-out-alt"></i><span>Log out</span></a>
    </div>

    <script>
        function loadQuiz() {
            document.getElementById('spinner').style.display = 'block';
            fetch('get_quiz.php')
                .then(res => res.json())
                .then(data => {
                    document.getElementById('spinner').style.display = 'none';
                    const feedback = document.getElementById('feedback');
                    feedback.textContent = '';
                    feedback.classList.remove('text-success', 'text-danger');
                    if (data.message) {
                        document.getElementById('question-text').textContent = data.message;
                        document.getElementById('answers').innerHTML = '';
                        return;
                    }
                    document.getElementById('question-text').textContent = data.question;
                    const answersDiv = document.getElementById('answers');
                    answersDiv.innerHTML = '';
                    ['A', 'B', 'C', 'D'].forEach(letter => {
                        const btn = document.createElement('button');
                        btn.className = 'btn btn-outline-primary m-2';
                        btn.textContent = `${letter}. ${data[`answer_${letter.toLowerCase()}`]}`;
                        btn.onclick = () => submitAnswer(data.id, letter);
                        answersDiv.appendChild(btn);
                    });
                });
        }

        function submitAnswer(questionId, selected) {
            fetch('submit_answer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `question_id=${questionId}&selected_answer=${selected}`
            })
            .then(res => res.json())
            .then(data => {
                const feedback = document.getElementById('feedback');
                document.querySelectorAll('#answers button').forEach(b => b.disabled = true);
                if (data.correct) {
                    feedback.textContent = `✅ Correct! You earned ${data.reward} coins.`;
                    feedback.classList.add('text-success');
                    new Audio('sounds/correct.mp3').play();
                } else {
                    feedback.textContent = `❌ Wrong. Correct answer was: ${data.correct_answer}`;
                    feedback.classList.add('text-danger');
                    new Audio('sounds/wrong.mp3').play();
                }
                setTimeout(loadQuiz, 5000);
            });
        }

        document.addEventListener('DOMContentLoaded', loadQuiz);
    </script>
</body>
</html>
