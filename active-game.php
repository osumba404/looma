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
    $user = ['username' => 'Guest', 'full_name' => '']; // Fallback
}

try {
    // Begin transaction for atomic updates
    $conn->begin_transaction();

    // Check spin eligibility
    $spin_eligibility = [
        'registration' => true,
        'weekly' => true,
        'bet' => true
    ];

    // Check if registration spin was used (ever)
    $stmt = $conn->prepare('SELECT COUNT(*) as spins FROM spins WHERE user_id = ? AND spin_type = "registration"');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()['spins'] > 0) {
        $spin_eligibility['registration'] = false;
    }
    $stmt->close();

    // Check if weekly spin was used this week
    $stmt = $conn->prepare('SELECT COUNT(*) as spins FROM spins WHERE user_id = ? AND spin_type = "weekly" AND played_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 WEEK)');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()['spins'] > 0) {
        $spin_eligibility['weekly'] = false;
    }
    $stmt->close();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['spin_type'])) {
        $spin_type = $_POST['spin_type'];
        $stake = isset($_POST['stake']) ? floatval($_POST['stake']) : 0;
        $max_win = 0;
        $reward_source = 'normal';

        // Validate spin type and eligibility
        if ($spin_type === 'registration' && $spin_eligibility['registration']) {
            $max_win = 250; // Ksh 250 for registration spin
            $reward_source = 'registration';
        } elseif ($spin_type === 'weekly' && $spin_eligibility['weekly']) {
            $max_win = 500; // Ksh 500 for weekly spin
            $reward_source = 'loyalty';
        } elseif ($spin_type === 'bet' && $stake >= 100 && $stake <= 1000) {
            // Check wallet balance for bet spin
            $stmt = $conn->prepare('SELECT balance FROM wallet WHERE user_id = ?');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $balance = $stmt->get_result()->fetch_assoc()['balance'] ?? 0;
            $stmt->close();
            if ($balance >= $stake) {
                $max_win = $stake * 6; // Up to 600% profit
                $reward_source = 'normal';
            } else {
                $result = '<div class="alert alert-danger">Insufficient wallet balance for stake.</div>';
            }
        } else {
            $result = '<div class="alert alert-danger">Invalid spin type or spin already used.</div>';
        }

        if ($max_win > 0) {
            // Deduct stake for bet spin
            if ($spin_type === 'bet') {
                $stmt = $conn->prepare('UPDATE wallet SET balance = balance - ?, last_interact = CURRENT_TIMESTAMP WHERE user_id = ?');
                $stmt->bind_param('di', $stake, $user_id);
                $stmt->execute();
                $stmt->close();
            }

            // Simulate spin (random win between 0 and max_win)
            $win_amount = rand(0, $max_win);

            // Update wallet with win amount
            $stmt = $conn->prepare('UPDATE wallet SET balance = balance + ?, last_interact = CURRENT_TIMESTAMP WHERE user_id = ?');
            $stmt->bind_param('di', $win_amount, $user_id);
            $stmt->execute();
            $stmt->close();

            // Update user_game_history
            $points_earned = $win_amount / 10; // Convert Ksh to points (1 Ksh = 0.1 points)
            $stmt = $conn->prepare('INSERT INTO user_game_history (user_id, game_type, points_earned) VALUES (?, "spin", ?)');
            $stmt->bind_param('id', $user_id, $points_earned);
            $stmt->execute();
            $stmt->close();

            // Update points table
            $stmt = $conn->prepare('INSERT INTO points (user_id, points) VALUES (?, ?) ON DUPLICATE KEY UPDATE points = points + ?');
            $stmt->bind_param('iid', $user_id, $points_earned, $points_earned);
            $stmt->execute();
            $stmt->close();

            // Insert into spins table
            $stmt = $conn->prepare('INSERT INTO spins (user_id, spin_type, stake, win_amount, spin_status, reward_source, ip_address) VALUES (?, ?, ?, ?, "confirmed", ?, ?)');
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $stmt->bind_param('isddss', $user_id, $spin_type, $stake, $win_amount, $reward_source, $ip_address);
            $stmt->execute();
            $stmt->close();

            // Insert into spin_rewards table
            $stmt = $conn->prepare('INSERT INTO spin_rewards (user_id, reward) VALUES (?, ?)');
            $stmt->bind_param('id', $user_id, $win_amount);
            $stmt->execute();
            $stmt->close();

            // Commit transaction
            $conn->commit();

            $result = '<div class="alert alert-success">Congratulations! You won Ksh ' . number_format($win_amount, 2) . '!</div>';
        } else {
            $conn->rollback();
        }
    } else {
        $conn->rollback();
    }
} catch (Exception $e) {
    $conn->rollback();
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
    <title>Looma | Spin & Earn</title>
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
        .spin-wheel {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 2rem;
        }
        .wheel {
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: conic-gradient(
                var(--primary-color) 0% 20%,
                var(--secondary-color) 20% 40%,
                var(--accent-color) 40% 60%,
                var(--light-color) 60% 80%,
                var(--dark-color) 80% 100%
            );
            position: relative;
            transition: transform 0.1s;
        }
        .wheel-pointer {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-bottom: 30px solid var(--dark-color);
        }
        .spin-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .spin-btn:hover {
            background-color: var(--secondary-color);
        }
        .spin-result {
            text-align: center;
            margin-top: 2rem;
            color: var(--dark-color);
            font-weight: 600;
        }
        .bet-input {
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
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(3600deg); }
        }
        .card {
            border-radius: 10px;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
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
            <a href="index1.php" class="nav-link">
                <i class="fas fa-home"></i><span>Dashboard</span>
            </a>
            <a href="games.php" class="nav-link">
                <i class="fas fa-gamepad"></i><span>Games</span>
            </a>
            <a href="questions.php" class="nav-link">
                <i class="fas fa-book"></i><span>Quizes</span>
            </a>
            <a href="wallet1.php" class="nav-link">
                <i class="fas fa-chart-line"></i><span>Earnings</span>
            </a>
            <a href="referrals.php" class="nav-link">
                <i class="fas fa-users"></i><span>Referrals</span>
            </a>
            <a href="achievements.php" class="nav-link">
                <i class="fas fa-trophy"></i><span>Leaderboard</span>
            </a>
            <a href="settings.php" class="nav-link">
                <i class="fas fa-cog"></i><span>Settings</span>
            </a>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i><span>Log out</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <p>© 2025 Looma</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
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
                <h2 class="mb-4">Spin & Earn</h2>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="spin-wheel">
                                    <div class="wheel" id="spinWheel">
                                        <div class="wheel-pointer"></div>
                                    </div>
                                </div>
                                <form method="POST" id="spinForm">
                                    <div class="mb-3">
                                        <label class="form-label">Spin Type:</label>
                                        <select class="form-select" name="spin_type" required>
                                            <option value="registration" <?php echo !$spin_eligibility['registration'] ? 'disabled' : ''; ?>>Registration Spin (Up to Ksh 250, 1-time)</option>
                                            <option value="weekly" <?php echo !$spin_eligibility['weekly'] ? 'disabled' : ''; ?>>Free Weekly Spin (Up to Ksh 500, 1/week)</option>
                                            <option value="bet">Bet Spin (Stake Ksh 100-1,000)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 bet-input-container" style="display: none;">
                                        <label class="form-label">Stake Amount (Ksh):</label>
                                        <input type="number" name="stake" class="bet-input" min="100" max="1000" placeholder="Enter Ksh 100-1000">
                                    </div>
                                    <button type="submit" class="spin-btn" id="spinBtn">Spin Now</button>
                                </form>
                                <div class="spin-result mt-3" id="spinResult"></div>
                                <p class="mt-3 text-muted">Note: Bet Spin resembles gambling and should be approached with caution.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="index.php" class="mobile-nav-item">
            <i class="fas fa-home"></i><span>Home</span>
        </a>
        <a href="games.php" class="mobile-nav-item">
            <i class="fas fa-gamepad"></i><span>Games</span>
        </a>
        <a href="questions.php" class="mobile-nav-item">
            <i class="fas fa-book"></i><span>Quizzes</span>
        </a>
        <a href="wallet.php" class="mobile-nav-item">
            <i class="fas fa-wallet"></i><span>Earnings</span>
        </a>
        <a href="settings.php" class="mobile-nav-item">
            <i class="fas fa-user"></i><span>Account</span>
        </a>
        <a href="logout.php" class="mobile-nav-item">
            <i class="fas fa-sign-out-alt"></i><span>Log out</span>
        </a>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('mainContent').classList.toggle('main-content-expanded');
        });

        // Responsive sidebar for mobile
        function handleResize() {
            if (window.innerWidth < 992) {
                document.getElementById('sidebar').classList.remove('active');
                document.getElementById('mainContent').classList.remove('main-content-expanded');
            } else {
                document.getElementById('sidebar').classList.add('active');
            }
        }

        window.addEventListener('resize', handleResize);
        document.addEventListener('DOMContentLoaded', handleResize);

        // Spin wheel animation
        let isSpinning = false;
        document.getElementById('spinForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (isSpinning) return;

            const spinType = document.querySelector('select[name="spin_type"]').value;
            const stakeInput = document.querySelector('input[name="stake"]');
            const betInputContainer = document.querySelector('.bet-input-container');

            if (spinType === 'bet' && (!stakeInput.value || stakeInput.value < 100 || stakeInput.value > 1000)) {
                alert('Please enter a stake between Ksh 100 and Ksh 1,000.');
                return;
            }

            isSpinning = true;
            const wheel = document.getElementById('spinWheel');
            wheel.style.animation = 'none';
            wheel.offsetHeight; // Trigger reflow
            wheel.style.animation = 'spin 4s ease-out';
            document.getElementById('spinBtn').disabled = true;

            setTimeout(() => {
                isSpinning = false;
                wheel.style.animation = 'none';
                document.getElementById('spinBtn').disabled = false;
                this.submit();
            }, 4000);
        });

        // Show/hide bet input based on spin type
        document.querySelector('select[name="spin_type"]').addEventListener('change', function() {
            const betInputContainer = document.querySelector('.bet-input-container');
            if (this.value === 'bet') {
                betInputContainer.style.display = 'block';
            } else {
                betInputContainer.style.display = 'none';
            }
        });
    </script>
</body>
</html>