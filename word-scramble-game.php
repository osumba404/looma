<?php
session_start();
require_once 'includes/db.php';

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
$scramble_result = '';

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
    $user = ['username' => 'Guest', 'full_name' => '']; // Fallback
}

// Handle Word Scramble Logic
$game_type_scramble = 'word_scramble';
$scramble_data = null;
$scramble_type = isset($_POST['scramble_type']) ? $_POST['scramble_type'] : 'single';

try {
    // Fetch a random word based on the scramble type
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_scramble'])) {
        if ($scramble_type === 'single') {
            $stmt = $conn->prepare('SELECT * FROM word_scramble_single ORDER BY RAND() LIMIT 1');
        } elseif ($scramble_type === 'multiple') {
            $stmt = $conn->prepare('SELECT * FROM word_scramble_multiple ORDER BY RAND() LIMIT 1');
        } elseif ($scramble_type === 'anagrams') {
            $stmt = $conn->prepare('SELECT * FROM word_scramble_anagrams ORDER BY RAND() LIMIT 1');
        }

        $stmt->execute();
        $scramble_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Store the correct answer in the session to validate later
        $_SESSION['scramble_data'] = $scramble_data;
        $_SESSION['scramble_type'] = $scramble_type;
    }

    // Validate the user's answer
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scramble_answer'])) {
        $user_answer = trim(strtolower($_POST['scramble_answer']));
        $scramble_data = $_SESSION['scramble_data'] ?? null;
        $scramble_type = $_SESSION['scramble_type'] ?? 'single';

        if ($scramble_data) {
            $win_amount = 0;
            $is_correct = false;

            if ($scramble_type === 'single') {
                $correct_word = strtolower($scramble_data['correct_word']);
                if ($user_answer === $correct_word) {
                    $is_correct = true;
                    $win_amount = 50;
                }
            } elseif ($scramble_type === 'multiple') {
                $correct_words = array_map('strtolower', array_map('trim', explode(',', $scramble_data['correct_words'])));
                $user_words = array_map('strtolower', array_map('trim', explode(',', $user_answer)));
                sort($correct_words);
                sort($user_words);
                if ($user_words === $correct_words) {
                    $is_correct = true;
                    $win_amount = 100;
                }
            } elseif ($scramble_type === 'anagrams') {
                $possible_words = array_map('strtolower', array_map('trim', explode(',', $scramble_data['possible_words'])));
                $user_words = array_map('strtolower', array_map('trim', explode(',', $user_answer)));
                $all_correct = true;
                foreach ($user_words as $word) {
                    if (!in_array($word, $possible_words)) {
                        $all_correct = false;
                        break;
                    }
                }
                if ($all_correct && count($user_words) === count($possible_words)) {
                    $is_correct = true;
                    $win_amount = 150;
                }
            }

            if ($is_correct) {
                $scramble_result = '<script>showWinModal(' . $win_amount . ');</script>';

                // Update wallet
                $stmt = $conn->prepare('UPDATE wallet SET balance = balance + ?, last_interact = CURRENT_TIMESTAMP WHERE user_id = ?');
                $stmt->bind_param('di', $win_amount, $user_id);
                $stmt->execute();
                $stmt->close();

                // Update user_game_history
                $points_earned = $win_amount / 10;
                $stmt = $conn->prepare('INSERT INTO user_game_history (user_id, game_type, points_earned) VALUES (?, ?, ?)');
                $stmt->bind_param('isd', $user_id, $game_type_scramble, $points_earned);
                $stmt->execute();
                $stmt->close();

                // Update points table
                $stmt = $conn->prepare('INSERT INTO points (user_id, points) VALUES (?, ?) ON DUPLICATE KEY UPDATE points = points + ?');
                $stmt->bind_param('iid', $user_id, $points_earned, $points_earned);
                $stmt->execute();
                $stmt->close();

                // Insert into scramble_rewards table
                $stmt = $conn->prepare('INSERT INTO scramble_rewards (user_id, reward) VALUES (?, ?)');
                $stmt->bind_param('id', $user_id, $win_amount);
                $stmt->execute();
                $stmt->close();

                // Clear session data after a correct answer
                unset($_SESSION['scramble_data']);
                unset($_SESSION['scramble_type']);
            } else {
                $scramble_result = '<div class="alert alert-danger">Incorrect answer. Try again!</div>';
            }
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
    <title>Looma | Word Scramble</title>
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
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
        }
        .game-section {
            padding: 2rem 0;
        }
        .scramble-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .scramble-btn:hover {
            background-color: var(--secondary-color);
        }
        .scramble-btn:disabled {
            background: #666;
            cursor: not-allowed;
        }
        .scramble-input {
            margin: 1rem 0;
            padding: 0.5rem;
            border: 2px solid var(--light-color);
            border-radius: 5px;
            width: 100%;
            max-width: 200px;
        }
        .alert {
            margin-top: 1rem;
        }
        .card {
            border-radius: 10px;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .modal-content {
            border-radius: 10px;
            background: var(--light-color);
        }
        .modal-header {
            background: var(--primary-color);
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .modal-title {
            font-weight: 600;
        }
        .modal-body {
            font-size: 1.2rem;
            text-align: center;
            color: var(--dark-color);
        }
        .modal-footer .btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
        }
        .scramble-word {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        .scramble-clue {
            font-style: italic;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }
        .scramble-result {
            text-align: center;
            margin-top: 2rem;
            color: var(--dark-color);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Desktop Sidebar -->
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
                <?php echo $scramble_result; ?>
                <h2 class="mb-4">Word Scramble</h2>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <form method="POST" id="scrambleForm">
                                    <div class="mb-3">
                                        <label class="form-label">Scramble Type:</label>
                                        <select class="form-select" name="scramble_type" id="scrambleType">
                                            <option value="single">Single Word</option>
                                            <option value="multiple">Multiple Words</option>
                                            <option value="anagrams">Anagrams</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="scramble-btn" name="fetch_scramble" value="1">Get Word</button>
                                </form>

                                <?php if ($scramble_data): ?>
                                    <div class="scramble-word">
                                        <?php
                                        if ($scramble_type === 'single') {
                                            echo htmlspecialchars($scramble_data['scrambled_word']);
                                        } elseif ($scramble_type === 'multiple') {
                                            echo htmlspecialchars($scramble_data['scrambled_letters']);
                                        } elseif ($scramble_type === 'anagrams') {
                                            echo htmlspecialchars($scramble_data['letters']);
                                        }
                                        ?>
                                    </div>
                                    <div class="scramble-clue">
                                        Clue: <?php echo htmlspecialchars($scramble_data['clue']); ?>
                                    </div>
                                    <form method="POST" id="scrambleAnswerForm">
                                        <div class="mb-3">
                                            <label class="form-label">Your Answer:</label>
                                            <input type="text" name="scramble_answer" class="scramble-input" placeholder="<?php echo $scramble_type === 'multiple' || $scramble_type === 'anagrams' ? 'Enter words separated by commas' : 'Enter the word'; ?>" required>
                                        </div>
                                        <button type="submit" class="scramble-btn">Submit Answer</button>
                                    </form>
                                <?php endif; ?>
                                <div class="scramble-result mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Pop-up Modal -->
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

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="index1.php" class="mobile-nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="games.php" class="mobile-nav-item active">
            <i class="fas fa-gamepad"></i>
            <span>Games</span>
        </a>
        <a href="wallet1.php" class="mobile-nav-item">
            <i class="fas fa-wallet"></i>
            <span>Earnings</span>
        </a>
        <a href="referrals.php" class="mobile-nav-item">
            <i class="fas fa-users"></i>
            <span>Refer</span>
        </a>
        <a href="settings.php" class="mobile-nav-item">
            <i class="fas fa-user"></i>
            <span>Account</span>
        </a>
        <a href="logout.php" class="mobile-nav-item">
            <i class="fas fa-sign-out-alt"></i> 
            <span>Log out</span>
        </a>
    </div>

    <script>
        // Toggle sidebar for desktop and mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('main-content-expanded');
        }

        // Responsive navigation handling
        function handleResize() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            if (window.innerWidth < 992) {
                sidebar.classList.remove('active'); // Ensure sidebar is hidden on mobile
                mainContent.classList.remove('main-content-expanded');
            } else {
                sidebar.classList.add('active'); // Show sidebar on desktop
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
    </script>
</body>
</html>