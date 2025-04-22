<?php
session_start();
require_once 'includes/db.php';

// Check database connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

$user_id = $_SESSION['user_id'];
$error = '';
$result = '';

// Fetch user data
$user = null;
try {
    $stmt = $conn->prepare('SELECT full_name, username FROM users WHERE user_id = ?');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    $error = '<div class="alert alert-danger">Error fetching user data: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Check if user data was fetched successfully
if (!$user) {
    $error .= '<div class="alert alert-danger">User not found or invalid session.</div>';
    $user = ['username' => 'Guest', 'full_name' => ''];
}

// Ensure game_rewards table exists
try {
    $conn->query("
        CREATE TABLE IF NOT EXISTS `game_rewards` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `game_type` VARCHAR(50) NOT NULL,
            `reward` DECIMAL(10,2) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
} catch (Exception $e) {
    $error = '<div class="alert alert-danger">Error creating game_rewards table: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Ensure wallet row exists
try {
    $stmt = $conn->prepare('SELECT user_id FROM wallet WHERE user_id = ?');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        $stmt = $conn->prepare('INSERT INTO wallet (user_id, balance, last_interact) VALUES (?, 0.00, CURRENT_TIMESTAMP)');
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
    }
    $stmt->close();
} catch (Exception $e) {
    $error = '<div class="alert alert-danger">Error initializing wallet: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Handle Math Blitz game logic
$game_type = 'math_blitz';
$difficulty = isset($_POST['difficulty']) ? $_POST['difficulty'] : 'easy';
$win_amount = 0;
$points_earned = 0;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_result'])) {
        $correct_answers = intval($_POST['correct_answers']);
        $attempts = intval($_POST['attempts']);
        $time_taken = floatval($_POST['time_taken']);
        $status = $_POST['status'];

        if ($status === 'win') {
            // Define game parameters
            $base_rewards = ['easy' => 25, 'medium' => 50, 'hard' => 80];
            $min_correct = ['easy' => 29, 'medium' => 29, 'hard' => 29];

            if (isset($base_rewards[$difficulty]) && $correct_answers >= $min_correct[$difficulty]) {
                // Check daily reward cap (100 Ksh)
                $today = date('Y-m-d');
                $stmt = $conn->prepare('SELECT SUM(reward) as total FROM game_rewards WHERE user_id = ? AND game_type = ? AND DATE(created_at) = ?');
                if ($stmt === false) {
                    throw new Exception('Prepare failed for reward check: ' . $conn->error);
                }
                $stmt->bind_param('iss', $user_id, $game_type, $today);
                $stmt->execute();
                $total_rewards = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
                $stmt->close();

                if ($total_rewards >= 100) {
                    $error = '<div class="alert alert-warning">Daily reward limit of 100 Ksh reached for Math Blitz.</div>';
                } else {
                    // Calculate reward with diminishing returns
                    $win_count = $conn->prepare('SELECT COUNT(*) as wins FROM game_rewards WHERE user_id = ? AND game_type = ? AND DATE(created_at) = ?');
                    if ($stmt === false) {
                        throw new Exception('Prepare failed for win count: ' . $conn->error);
                    }
                    $win_count->bind_param('iss', $user_id, $game_type, $today);
                    $win_count->execute();
                    $wins_today = $win_count->get_result()->fetch_assoc()['wins'];
                    $win_count->close();

                    $win_amount = $base_rewards[$difficulty] * pow(0.8, $wins_today); // 20% reduction per win
                    $win_amount = min($win_amount, 100 - $total_rewards); // Enforce daily cap
                    $points_earned = $win_amount / 10;

                    // Update wallet
                    $stmt = $conn->prepare('UPDATE wallet SET balance = balance + ?, last_interact = CURRENT_TIMESTAMP WHERE user_id = ?');
                    if ($stmt === false) {
                        throw new Exception('Prepare failed for wallet update: ' . $conn->error);
                    }
                    $stmt->bind_param('di', $win_amount, $user_id);
                    $stmt->execute();
                    $stmt->close();

                    // Update user_game_history
                    $stmt = $conn->prepare('INSERT INTO user_game_history (user_id, game_type, points_earned) VALUES (?, ?, ?)');
                    if ($stmt === false) {
                        throw new Exception('Prepare failed for game history: ' . $conn->error);
                    }
                    $stmt->bind_param('isd', $user_id, $game_type, $points_earned);
                    $stmt->execute();
                    $stmt->close();

                    // Update points table
                    $stmt = $conn->prepare('INSERT INTO points (user_id, points) VALUES (?, ?) ON DUPLICATE KEY UPDATE points = points + ?');
                    if ($stmt === false) {
                        throw new Exception('Prepare failed for points update: ' . $conn->error);
                    }
                    $stmt->bind_param('iid', $user_id, $points_earned, $points_earned);
                    $stmt->execute();
                    $stmt->close();

                    // Insert into game_rewards
                    $stmt = $conn->prepare('INSERT INTO game_rewards (user_id, game_type, reward) VALUES (?, ?, ?)');
                    if ($stmt === false) {
                        throw new Exception('Prepare failed for game rewards: ' . $conn->error);
                    }
                    $stmt->bind_param('isd', $user_id, $game_type, $win_amount);
                    $stmt->execute();
                    $stmt->close();

                    $result = '<script>showWinModal(' . $win_amount . ');</script>';
                }
            } else {
                $error = '<div class="alert alert-danger">Invalid difficulty or insufficient correct answers.</div>';
            }
        } else {
            $result = '<script>showGameOverModal("' . htmlspecialchars($status) . '");</script>';
        }
    }
} catch (Exception $e) {
    $error = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Get user initials
$initials = '';
$name_parts = explode(' ', $user['full_name']);
if (count($name_parts) >= 1) {
    $initials .= strtoupper(substr($name_parts[0], 0, 1));
    if (count($name_parts) > 1) {
        $initials .= strtoupper(substr($name_parts[1], 0, 1));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | Math Blitz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6c5ce7;
            --secondary-color: #00cec9;
            --accent-color: #fd79a8;
            --dark-color: #2d3436;
            --light-color: #f5f6fa;
        }
        .game-section {
            padding: 2rem 0;
        }
        .question-box {
            background: var(--light-color);
            border: 2px solid var(--dark-color);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            text-align: center;
        }
        .answer-input {
            font-size: 1.1rem;
            text-align: center;
        }
        .game-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .game-btn:hover {
            background-color: var(--secondary-color);
        }
        .game-btn:disabled {
            background: #666;
            cursor: not-allowed;
        }
        .game-info {
            margin-bottom: 1rem;
            font-size: 1rem;
            color: var(--dark-color);
        }
        .alert {
            margin-top: 1rem;
        }
        .modal-content {
            border-radius: 10px;
            background: var(--light-color);
        }
        .modal-header {
            background: var(--primary-color);
            color: white;
        }
        .modal-title {
            font-weight: 600;
        }
        .modal-body {
            font-size: 1.2rem;
            text-align: center;
        }
        .modal-footer .btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
        }
        @media (max-width: 576px) {
            .question-box {
                font-size: 1.1rem;
                padding: 0.8rem;
            }
            .answer-input {
                font-size: 1rem;
            }
            .game-btn {
                padding: 6px 12px;
                font-size: 0.9rem;
            }
            .game-info {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h2>LOOMA</h2>
            <p>Earn While You Play</p>
        </div>
        <nav class="nav flex-column">
            <a href="index1.php" class="nav-link"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="games.php" class="nav-link active"><i class="fas fa-gamepad"></i><span>Games</span></a>
            <a href="questions.php" class="nav-link"><i class="fas fa-book"></i><span>Quizes</span></a>
            <a href="wallet1.php" class="nav-link"><i class="fas fa-chart-line"></i><span>Earnings</span></a>
            <a href="referrals.php" class="nav-link"><i class="fas fa-users"></i><span>Referrals</span></a>
            <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i><span>Log out</span></a>
        </nav>
        <div class="sidebar-footer">
            <p>© 2025 Looma</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <h2>LOOMA</h2>
            <div class="user-profile">
                <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>
            </div>
        </div>

        <!-- Game Section -->
        <section class="game-section py-4 animate-fadeIn">
            <div class="container">
                <?php if ($error): ?>
                    <?php echo $error; ?>
                <?php endif; ?>
                <?php echo $result; ?>
                <h2 class="mb-4">Math Blitz</h2>
                <div class="row justify-content-center">
                    <div class="col-md-6 col-sm-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <form method="POST" id="gameForm">
                                    <div class="mb-3">
                                        <label class="form-label">Difficulty:</label>
                                        <select class="form-select" name="difficulty" id="difficulty">
                                            <option value="easy">Easy (29/30 correct, 10 attempts, 30s)</option>
                                            <option value="medium">Medium (29/30 correct, 8 attempts, 25s)</option>
                                            <option value="hard">Hard (29/30 correct, 6 attempts, 20s)</option>
                                        </select>
                                    </div>
                                    <button type="button" class="game-btn" id="startGame">Start Game</button>
                                </form>
                                <div class="game-info">
                                    <span>Correct: <span id="correctAnswers">0</span>/<span id="minCorrect">29</span></span> |
                                    <span>Attempts: <span id="attempts">0</span>/<span id="maxAttempts">10</span></span> |
                                    <span>Time: <span id="timer">0</span>s/<span id="maxTime">30</span>s</span>
                                </div>
                                <div id="questionBox" class="question-box d-none"></div>
                                <input type="number" id="answerInput" class="form-control answer-input d-none mb-3" placeholder="Enter answer" step="0.01">
                                <button type="button" id="submitAnswer" class="game-btn d-none">Submit</button>
                                <form method="POST" id="resultForm" style="display: none;">
                                    <input type="hidden" name="game_result" value="1">
                                    <input type="hidden" name="difficulty" id="resultDifficulty">
                                    <input type="hidden" name="correct_answers" id="resultCorrect">
                                    <input type="hidden" name="attempts" id="resultAttempts">
                                    <input type="hidden" name="time_taken" id="resultTime">
                                    <input type="hidden" name="status" id="resultStatus">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Win Modal -->
    <div class="modal fade" id="winModal" tabindex="-1" aria-labelledby="winModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="winModalLabel">Congratulations!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="winMessage">
                    You won Ksh 0!
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Game Over Modal -->
    <div class="modal fade" id="gameOverModal" tabindex="-1" aria-labelledby="gameOverModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="gameOverModalLabel">Game Over!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="gameOverMessage">
                    You ran out of time or attempts!
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div>
        <a href="games.php" class="btn btn-primary" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">More Games</a>
    </div>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="index1.php" class="mobile-nav-item"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="games.php" class="mobile-nav-item active"><i class="fas fa-gamepad"></i><span>Games</span></a>
        <a href="wallet1.php" class="mobile-nav-item"><i class="fas fa-wallet"></i><span>Earnings</span></a>
        <a href="referrals.php" class="mobile-nav-item"><i class="fas fa-users"></i><span>Refer</span></a>
        <a href="settings.php" class="mobile-nav-item"><i class="fas fa-user"></i><span>Account</span></a>
        <a href="logout.php" class="mobile-nav-item"><i class="fas fa-sign-out-alt"></i><span>Log out</span></a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('main-content-expanded');
        }

        // Responsive navigation
        function handleResize() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            if (window.innerWidth < 992) {
                sidebar.classList.remove('active');
                mainContent.classList.remove('main-content-expanded');
            } else {
                sidebar.classList.add('active');
                mainContent.classList.remove('main-content-expanded');
            }
        }
        window.addEventListener('resize', handleResize);
        document.addEventListener('DOMContentLoaded', handleResize);

        // Modal functions
        function showWinModal(amount) {
            const winModal = new bootstrap.Modal(document.getElementById('winModal'));
            document.getElementById('winMessage').innerText = `You won Ksh ${amount}!`;
            winModal.show();
        }

        function showGameOverModal(reason) {
            const gameOverModal = new bootstrap.Modal(document.getElementById('gameOverModal'));
            document.getElementById('gameOverMessage').innerText = `Game Over: ${reason}!`;
            gameOverModal.show();
        }

        // Math Blitz game logic
        const startButton = document.getElementById('startGame');
        const difficultySelect = document.getElementById('difficulty');
        const questionBox = document.getElementById('questionBox');
        const answerInput = document.getElementById('answerInput');
        const submitButton = document.getElementById('submitAnswer');
        const correctDisplay = document.getElementById('correctAnswers');
        const attemptsDisplay = document.getElementById('attempts');
        const timerDisplay = document.getElementById('timer');
        const minCorrectDisplay = document.getElementById('minCorrect');
        const maxAttemptsDisplay = document.getElementById('maxAttempts');
        const maxTimeDisplay = document.getElementById('maxTime');
        const resultForm = document.getElementById('resultForm');

        let questions = [];
        let currentQuestion = 0;
        let correctAnswers = 0;
        let attempts = 0;
        let timeTaken = 0;
        let timerInterval = null;
        let minCorrect = 29;
        let maxAttempts = 10;
        let maxTime = 30;
        let incorrectAnswers = 0;

        function generateDistractors(answer) {
            const distractors = [];
            for (let i = 0; i < 2; i++) {
                let distractor;
                do {
                    const offset = (Math.random() * 0.1 + 0.01) * (Math.random() < 0.5 ? 1 : -1);
                    distractor = Number((answer + answer * offset).toFixed(2));
                } while (distractor === answer || distractors.includes(distractor));
                distractors.push(distractor);
            }
            return distractors;
        }

        function randomSwapQuestions() {
            if (Math.random() < 0.5 && currentQuestion < questions.length - 1) {
                const unansweredIndices = questions
                    .map((q, i) => (i > currentQuestion ? i : -1))
                    .filter(i => i !== -1);
                if (unansweredIndices.length > 0) {
                    const swapIdx = unansweredIndices[Math.floor(Math.random() * unansweredIndices.length)];
                    [questions[currentQuestion], questions[swapIdx]] = [questions[swapIdx], questions[currentQuestion]];
                }
            }
        }

        function generateQuestions(difficulty) {
            questions = [];
            const operations = {
                easy: ['+', '-'],
                medium: ['+', '-', '*'],
                hard: ['+', '-', '*', '/']
            };
            const maxNumber = { easy: 100, medium: 200, hard: 500 };

            for (let i = 0; i < 30; i++) {
                const op = operations[difficulty][Math.floor(Math.random() * operations[difficulty].length)];
                let num1 = (Math.random() * maxNumber[difficulty]).toFixed(2);
                let num2 = (Math.random() * maxNumber[difficulty]).toFixed(2);
                let question, answer;

                if (op === '/' && difficulty === 'hard') {
                    const product = (num1 * num2).toFixed(2);
                    question = `${product} ÷ ${num1} = ?`;
                    answer = Number(num2);
                } else if (op === '*' && difficulty === 'medium') {
                    num1 = (Math.random() * 10).toFixed(2);
                    num2 = (Math.random() * 10).toFixed(2);
                    question = `${num1} ${op} ${num2} = ?`;
                    answer = Number((num1 * num2).toFixed(2));
                } else if (op === '-' && difficulty === 'easy') {
                    if (Math.random() < 0.5) {
                        num1 = (Math.random() * maxNumber[difficulty] * -1).toFixed(2);
                    }
                    question = `${num1} ${op} ${num2} = ?`;
                    answer = Number((num1 - num2).toFixed(2));
                } else {
                    question = `${num1} ${op} ${num2} = ?`;
                    answer = Number(eval(`${num1} ${op} ${num2}`).toFixed(2));
                }

                const distractors = generateDistractors(answer);
                questions.push({ question, answer, distractors });
            }
        }

        function showQuestion() {
            if (currentQuestion < 30 && attempts < maxAttempts && timeTaken < maxTime) {
                const q = questions[currentQuestion];
                const allAnswers = [q.answer, ...q.distractors].sort(() => Math.random() - 0.5);
                questionBox.innerHTML = `${q.question}<br><small>Possible answers: ${allAnswers.join(', ')}</small>`;
                answerInput.value = '';
                answerInput.focus();
            } else {
                checkGameEnd();
            }
        }

        function startTimer() {
            timeTaken = 0;
            timerDisplay.innerText = timeTaken;
            timerInterval = setInterval(() => {
                timeTaken++;
                timerDisplay.innerText = timeTaken;
                if (timeTaken >= maxTime / 2 && maxAttempts > 1) {
                    maxAttempts = Math.floor(maxAttempts / 2);
                    maxAttemptsDisplay.innerText = maxAttempts;
                }
                if (timeTaken >= maxTime) {
                    endGame('Maximum time exceeded');
                }
            }, 1000);
        }

        function stopTimer() {
            clearInterval(timerInterval);
        }

        function checkGameEnd() {
            if (correctAnswers >= minCorrect && incorrectAnswers === 0) {
                endGame('win');
            } else {
                endGame('Insufficient correct answers, maximum attempts exceeded, or incorrect answer');
            }
        }

        function endGame(status) {
            stopTimer();
            questionBox.classList.add('d-none');
            answerInput.classList.add('d-none');
            submitButton.classList.add('d-none');
            document.getElementById('resultDifficulty').value = difficultySelect.value;
            document.getElementById('resultCorrect').value = correctAnswers;
            document.getElementById('resultAttempts').value = attempts;
            document.getElementById('resultTime').value = timeTaken;
            document.getElementById('resultStatus').value = status;
            resultForm.submit();
        }

        function resetGame() {
            questions = [];
            currentQuestion = 0;
            correctAnswers = 0;
            attempts = 0;
            timeTaken = 0;
            incorrectAnswers = 0;
            correctDisplay.innerText = correctAnswers;
            attemptsDisplay.innerText = attempts;
            timerDisplay.innerText = timeTaken;
            questionBox.classList.add('d-none');
            answerInput.classList.add('d-none');
            submitButton.classList.add('d-none');
            stopTimer();
        }

        startButton.addEventListener('click', () => {
            resetGame();
            switch (difficultySelect.value) {
                case 'easy':
                    minCorrect = 29;
                    maxAttempts = 10;
                    maxTime = 30;
                    break;
                case 'medium':
                    minCorrect = 29;
                    maxAttempts = 8;
                    maxTime = 25;
                    break;
                case 'hard':
                    minCorrect = 29;
                    maxAttempts = 6;
                    maxTime = 20;
                    break;
            }
            minCorrectDisplay.innerText = minCorrect;
            maxAttemptsDisplay.innerText = maxAttempts;
            maxTimeDisplay.innerText = maxTime;
            generateQuestions(difficultySelect.value);
            questionBox.classList.remove('d-none');
            answerInput.classList.remove('d-none');
            submitButton.classList.remove('d-none');
            startTimer();
            showQuestion();
            startButton.disabled = true;
            difficultySelect.disabled = true;
        });

        submitButton.addEventListener('click', () => {
            if (attempts < maxAttempts && timeTaken < maxTime) {
                const userAnswer = parseFloat(answerInput.value);
                const correctAnswer = questions[currentQuestion].answer;
                attempts++;
                attemptsDisplay.innerText = attempts;

                if (Math.abs(userAnswer - correctAnswer) < 0.01) {
                    correctAnswers++;
                    correctDisplay.innerText = correctAnswers;
                } else {
                    incorrectAnswers++;
                }

                currentQuestion++;
                randomSwapQuestions();
                showQuestion();
            }
        });

        answerInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                submitButton.click();
            }
        });

        difficultySelect.addEventListener('change', () => {
            resetGame();
            startButton.disabled = false;
            difficultySelect.disabled = false;
        });
    </script>
</body>
</html>