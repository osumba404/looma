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

// Create game_rewards table if it doesn't exist
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

// Handle Memory Match game logic
$game_type = 'memory_match';
$difficulty = isset($_POST['difficulty']) ? $_POST['difficulty'] : 'easy';
$win_amount = 0;
$points_earned = 0;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_result'])) {
        $moves = intval($_POST['moves']);
        $time_taken = floatval($_POST['time_taken']);
        $status = $_POST['status'];

        if ($status === 'win') {
            // Calculate rewards
            $base_rewards = [
                'easy' => 25,
                'medium' => 50,
                'hard' => 80
            ];

            if (isset($base_rewards[$difficulty])) {
                $win_amount = $base_rewards[$difficulty];
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

                // Insert into game_rewards table
                $stmt = $conn->prepare('INSERT INTO game_rewards (user_id, game_type, reward) VALUES (?, ?, ?)');
                if ($stmt === false) {
                    throw new Exception('Prepare failed for game rewards: ' . $conn->error);
                }
                $stmt->bind_param('isd', $user_id, $game_type, $win_amount);
                $stmt->execute();
                $stmt->close();

                $result = '<script>showWinModal(' . $win_amount . ');</script>';
            } else {
                $error = '<div class="alert alert-danger">Invalid difficulty level.</div>';
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
    <title>Looma | Memory Match</title>
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
        .memory-grid {
            display: grid;
            gap: 5px;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .memory-card {
            width: 60px;
            height: 60px;
            background: var(--light-color);
            border: 2px solid var(--dark-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .memory-card.flipped, .memory-card.matched {
            background: var(--secondary-color);
            color: white;
            transform: rotateY(180deg);
        }
        .memory-card.matched {
            background: var(--accent-color);
        }
        .memory-card.distractor {
            background: #ccc;
            cursor: default;
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
            .memory-card {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
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
                <h2 class="mb-4">Memory Match</h2>
                <div class="row justify-content-center">
                    <div class="col-md-8 col-sm-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <form method="POST" id="gameForm">
                                    <div class="mb-3">
                                        <label class="form-label">Difficulty:</label>
                                        <select class="form-select" name="difficulty" id="difficulty">
                                            <option value="easy">Easy (6x4, 6 moves, 15s)</option>
                                            <option value="medium">Medium (8x5, 8 moves, 20s)</option>
                                            <option value="hard">Hard (10x6, 10 moves, 25s)</option>
                                        </select>
                                    </div>
                                    <button type="button" class="game-btn" id="startGame">Start Game</button>
                                </form>
                                <div class="game-info">
                                    <span>Moves: <span id="moves">0</span>/<span id="maxMoves">6</span></span> |
                                    <span>Time: <span id="timer">0</span>s/<span id="maxTime">15</span>s</span>
                                </div>
                                <div id="memoryGrid" class="memory-grid"></div>
                                <form method="POST" id="resultForm" style="display: none;">
                                    <input type="hidden" name="game_result" value="1">
                                    <input type="hidden" name="difficulty" id="resultDifficulty">
                                    <input type="hidden" name="moves" id="resultMoves">
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
                    You exceeded the maximum moves or time!
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
        // Toggle sidebar
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

        // Memory Match game logic
        const grid = document.getElementById('memoryGrid');
        const startButton = document.getElementById('startGame');
        const movesDisplay = document.getElementById('moves');
        const timerDisplay = document.getElementById('timer');
        const maxMovesDisplay = document.getElementById('maxMoves');
        const maxTimeDisplay = document.getElementById('maxTime');
        const difficultySelect = document.getElementById('difficulty');
        const resultForm = document.getElementById('resultForm');
        let cards = [];
        let flippedCards = [];
        let matchedPairs = 0;
        let moves = 0;
        let timeTaken = 0;
        let timerInterval = null;
        let maxMoves = 6;
        let maxTime = 15;
        let mismatches = 0;

        function createCards(difficulty) {
            let pairCount, rows, cols, distractorCount;
            switch (difficulty) {
                case 'easy':
                    pairCount = 12;
                    rows = 6;
                    cols = 4;
                    maxMoves = 6;
                    maxTime = 15;
                    distractorCount = 4;
                    break;
                case 'medium':
                    pairCount = 20;
                    rows = 8;
                    cols = 5;
                    maxMoves = 8;
                    maxTime = 20;
                    distractorCount = 6;
                    break;
                case 'hard':
                    pairCount = 30;
                    rows = 10;
                    cols = 6;
                    maxMoves = 10;
                    maxTime = 25;
                    distractorCount = 8;
                    break;
                default:
                    pairCount = 12;
                    rows = 6;
                    cols = 4;
                    maxMoves = 6;
                    maxTime = 15;
                    distractorCount = 4;
            }

            // Update UI with limits
            maxMovesDisplay.innerText = maxMoves;
            maxTimeDisplay.innerText = maxTime;

            // Set grid layout
            grid.style.gridTemplateColumns = `repeat(${cols}, 60px)`;
            grid.style.gridTemplateRows = `repeat(${rows}, 60px)`;

            // Generate card values (similar emojis)
            const emojis = ['😊', '🙂', '😃', '😄', '😺', '🐱', '🌟', '✨', '🍎', '🍏', '🍊', '🍑', '🍒', '🍓', '🍇', '🍉', '🍍', '🍋', '🍈', '🍐'];
            let selectedEmojis = emojis.slice(0, pairCount);
            let cardValues = [...selectedEmojis, ...selectedEmojis];
            for (let i = 0; i < distractorCount; i++) {
                cardValues.push('❓');
            }

            // Shuffle cards
            for (let i = cardValues.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [cardValues[i], cardValues[j]] = [cardValues[j], cardValues[i]];
            }

            // Create card elements
            cards = cardValues.map((value, index) => ({
                id: index,
                value,
                element: null,
                flipped: false,
                matched: value === '❓'
            }));

            // Clear grid
            grid.innerHTML = '';

            // Create card elements
            cards.forEach(card => {
                const cardElement = document.createElement('div');
                cardElement.classList.add('memory-card');
                if (card.matched) {
                    cardElement.classList.add('distractor');
                }
                cardElement.dataset.id = card.id;
                cardElement.innerText = '';
                if (!card.matched) {
                    cardElement.addEventListener('click', () => flipCard(card));
                }
                grid.appendChild(cardElement);
                card.element = cardElement;
            });
        }

        function randomSwapCards() {
            if (Math.random() < 0.5) {
                const unflippedCards = cards.filter(card => !card.flipped && !card.matched);
                if (unflippedCards.length >= 2) {
                    const idx1 = Math.floor(Math.random() * unflippedCards.length);
                    let idx2 = Math.floor(Math.random() * unflippedCards.length);
                    while (idx2 === idx1) {
                        idx2 = Math.floor(Math.random() * unflippedCards.length);
                    }
                    const card1 = unflippedCards[idx1];
                    const card2 = unflippedCards[idx2];
                    const tempValue = card1.value;
                    card1.value = card2.value;
                    card2.value = tempValue;
                }
            }
        }

        function flipCard(card) {
            if (flippedCards.length < 2 && !card.flipped && !card.matched && moves < maxMoves && timeTaken < maxTime) {
                card.flipped = true;
                card.element.classList.add('flipped');
                card.element.innerText = card.value;
                flippedCards.push(card);

                if (flippedCards.length === 2) {
                    moves++;
                    movesDisplay.innerText = moves;
                    checkMatch();
                }
                randomSwapCards();
            }
        }

        function checkMatch() {
            const [card1, card2] = flippedCards;
            if (card1.value === card2.value) {
                card1.matched = true;
                card2.matched = true;
                card1.element.classList.add('matched');
                card2.element.classList.add('matched');
                matchedPairs++;
                flippedCards = [];

                if (matchedPairs === Math.floor((cards.length - cards.filter(c => c.value === '❓').length) / 2)) {
                    endGame('win');
                } else if (moves >= maxMoves) {
                    endGame('Maximum moves exceeded');
                }
            } else {
                mismatches++;
                setTimeout(() => {
                    card1.flipped = false;
                    card2.flipped = false;
                    card1.element.classList.remove('flipped');
                    card2.element.classList.remove('flipped');
                    card1.element.innerText = '';
                    card2.element.innerText = '';
                    flippedCards = [];
                    if (moves >= maxMoves) {
                        endGame('Maximum moves exceeded');
                    } else if (mismatches > 0) {
                        endGame('Mismatch detected');
                    }
                }, 500);
            }
        }

        function startTimer() {
            timeTaken = 0;
            timerDisplay.innerText = timeTaken;
            timerInterval = setInterval(() => {
                timeTaken++;
                timerDisplay.innerText = timeTaken;
                if (timeTaken >= maxTime / 2 && maxMoves > 1) {
                    maxMoves = Math.floor(maxMoves / 2);
                    maxMovesDisplay.innerText = maxMoves;
                }
                if (timeTaken >= maxTime) {
                    endGame('Maximum time exceeded');
                }
            }, 1000);
        }

        function stopTimer() {
            clearInterval(timerInterval);
        }

        function endGame(status) {
            stopTimer();
            document.getElementById('resultDifficulty').value = difficultySelect.value;
            document.getElementById('resultMoves').value = moves;
            document.getElementById('resultTime').value = timeTaken;
            document.getElementById('resultStatus').value = status;
            resultForm.submit();
        }

        function resetGame() {
            cards = [];
            flippedCards = [];
            matchedPairs = 0;
            moves = 0;
            timeTaken = 0;
            mismatches = 0;
            movesDisplay.innerText = moves;
            timerDisplay.innerText = timeTaken;
            stopTimer();
            grid.innerHTML = '';
        }

        startButton.addEventListener('click', () => {
            resetGame();
            createCards(difficultySelect.value);
            startTimer();
            startButton.disabled = true;
            difficultySelect.disabled = true;
        });

        difficultySelect.addEventListener('change', () => {
            resetGame();
            startButton.disabled = false;
            difficultySelect.disabled = false;
        });
    </script>
</body>
</html>