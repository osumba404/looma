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

// Define reward sets for each spin type
$reward_sets = [
    'registration' => [0, 50, 100, 150, 200, 250], // Ksh rewards
    'weekly' => [0, 100, 200, 300, 400, 500],
    'bet' => [] // Will be calculated dynamically based on stake
];

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
        $reward_source = 'normal';
        $rewards = $reward_sets[$spin_type] ?? [];

        // Validate spin type and eligibility
        if ($spin_type === 'registration' && $spin_eligibility['registration']) {
            $reward_source = 'registration';
        } elseif ($spin_type === 'weekly' && $spin_eligibility['weekly']) {
            $reward_source = 'loyalty';
        } elseif ($spin_type === 'bet' && $stake >= 100 && $stake <= 1000) {
            // Check wallet balance for bet spin
            $stmt = $conn->prepare('SELECT balance FROM wallet WHERE user_id = ?');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $balance = $stmt->get_result()->fetch_assoc()['balance'] ?? 0;
            $stmt->close();
            if ($balance >= $stake) {
                // Dynamically generate rewards for bet spin (0 to 600% of stake)
                $max_win = $stake * 6;
                $rewards = [0, $stake * 0.5, $stake, $stake * 2, $stake * 4, $max_win];
            } else {
                $result = '<div class="alert alert-danger">Insufficient wallet balance for stake.</div>';
            }
        } else {
            $result = '<div class="alert alert-danger">Invalid spin type or spin already used.</div>';
        }

        if (!empty($rewards)) {
            // Deduct stake for bet spin
            if ($spin_type === 'bet') {
                $stmt = $conn->prepare('UPDATE wallet SET balance = balance - ?, last_interact = CURRENT_TIMESTAMP WHERE user_id = ?');
                $stmt->bind_param('di', $stake, $user_id);
                $stmt->execute();
                $stmt->close();
            }

            // Select random reward
            $win_index = array_rand($rewards);
            $win_amount = $rewards[$win_index];

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

            // Pass win amount and index to JavaScript for wheel animation and pop-up
            $result = '<script>const spinResult = ' . json_encode([
                'amount' => $win_amount,
                'rewards' => $rewards,
                'winIndex' => $win_index
            ]) . ';</script>';
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
            position: relative;
        }
        .wheel-container {
            position: relative;
            width: 300px;
            height: 300px;
        }
        .casino-wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px solid var(--dark-color);
        }
        .wheel-pointer {
            position: absolute;
            top: -20px;
            left: 45%;
            transform: rotate(180deg); 
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-bottom: 30px solid var(--secondary-color);
            z-index: 10;
            
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
        .spin-btn:disabled {
            background: #666;
            cursor: not-allowed;
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
    </style>
</head>
<body>
    <!-- Sidebar (unchanged) -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h2>LOOMA</h2>
            <p>Earn While You Play</p>
        </div>
        
        <nav class="nav flex-column">
            <a href="index1.php" class="nav-link">
                <i class="fas fa-home"></i><span>Dashboard</span>
            </a>
            <a href="games.php" class="nav-link active">
                <i class="fas fa-gamepad"></i><span>Games</span>
            </a>
            <a href="wallet1.php" class="nav-link">
                <i class="fas fa-chart-line"></i><span>Earnings</span>
            </a>
            <a href="referrals.php" class="nav-link">
                <i class="fas fa-users"></i><span>Referrals</span>
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
                <h2 class="mb-4">Spin & Earn</h2>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="spin-wheel">
                                    <div class="wheel-container">
                                        <canvas id="wheel" class="casino-wheel" width="300" height="300"></canvas>
                                        <div class="wheel-pointer"></div>
                                    </div>
                                </div>
                                <form method="POST" id="spinForm">
                                    <div class="mb-3">
                                        <label class="form-label">Spin Type:</label>
                                        <select class="form-select" name="spin_type" id="spinType" required>
                                            <option value="registration" <?php echo !$spin_eligibility['registration'] ? 'disabled' : ''; ?>>Registration Spin (Up to Ksh 250, 1-time)</option>
                                            <option value="weekly" <?php echo !$spin_eligibility['weekly'] ? 'disabled' : ''; ?>>Free Weekly Spin (Up to Ksh 500, 1/week)</option>
                                            <option value="bet">Bet Spin (Stake Ksh 100-1,000)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 bet-input-container">
                                        <label class="form-label">Stake Amount (Ksh):</label>
                                        <input type="number" name="stake" id="stakeInput" class="bet-input" min="100" max="1000" placeholder="Enter Ksh 100-1000">
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

    <div>
        <a href="games.php" class="btn btn-primary" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">More Games</a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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

        // Wheel setup
        const canvas = document.getElementById('wheel');
        const ctx = canvas.getContext('2d');
        const rewardSets = {
            registration: [0, 50, 100, 150, 200, 250],
            weekly: [0, 100, 200, 300, 400, 500],
            bet: [0, 0, 0, 0, 0, 0] // Placeholder, updated dynamically
        };
        const colors = ['#ff6f61', '#00cec9', '#fd79a8', '#6b7280', '#ed8936', '#9f7aea'];
        let currentRewards = rewardSets.registration;
        let isSpinning = false;

        function drawWheel(rewards) {
            const numSegments = rewards.length;
            const angle = (2 * Math.PI) / numSegments;
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Multiplier labels for each segment
            const multiplierLabels = ['x2', 'x3', 'x1', 'x4', 'x6', 'x0'];

            for (let i = 0; i < numSegments; i++) {
                ctx.beginPath();
                ctx.moveTo(150, 150);
                ctx.arc(150, 150, 150, i * angle, (i + 1) * angle);
                ctx.fillStyle = colors[i % colors.length];
                ctx.fill();
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 2;
                ctx.stroke();

                // Draw multiplier label
                ctx.save();
                ctx.translate(150, 150);
                ctx.rotate(i * angle + angle / 2);
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 18px Poppins';
                ctx.textAlign = 'center';
                ctx.fillText(multiplierLabels[i] || '', 100, 0);
                ctx.restore();
            }
        }

        // Initial draw
        drawWheel(currentRewards);

        // Update wheel rewards based on spin type
        document.getElementById('spinType').addEventListener('change', function() {
            const spinType = this.value;
            const stakeInput = document.getElementById('stakeInput');
            const betInputContainer = document.querySelector('.bet-input-container');

            if (spinType === 'bet') {
                betInputContainer.style.display = 'block';
                stakeInput.addEventListener('input', function() {
                    const stake = parseFloat(this.value) || 0;
                    if (stake >= 100 && stake <= 1000) {
                        currentRewards = [0, stake * 0.5, stake, stake * 2, stake * 4, stake * 6];
                        drawWheel(currentRewards);
                    }
                });
            } else {
                betInputContainer.style.display = 'none';
                currentRewards = rewardSets[spinType];
                drawWheel(currentRewards);
            }
        });

        // Spin wheel
        document.getElementById('spinForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (isSpinning) return;

            const spinType = document.getElementById('spinType').value;
            const stakeInput = document.getElementById('stakeInput');
            if (spinType === 'bet' && (!stakeInput.value || stakeInput.value < 100 || stakeInput.value > 1000)) {
                alert('Please enter a stake between Ksh 100 and Ksh 1,000.');
                return;
            }

            isSpinning = true;
            const spinBtn = document.getElementById('spinBtn');
            spinBtn.disabled = true;

            // Trigger server-side spin
            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(data => {
                // Extract spinResult from response
                let spinResult = { amount: 0, rewards: currentRewards, winIndex: 0 };
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                const script = doc.querySelector('script:not([src])');
                if (script) {
                    const scriptContent = script.textContent;
                    const match = scriptContent.match(/const spinResult = (.*);/);
                    if (match) {
                        spinResult = JSON.parse(match[1]);
                    }
                }

                // Calculate rotation so the pointer lands at the center of the winning segment
                const numSegments = spinResult.rewards.length;
                const anglePerSegment = 360 / numSegments;
                const winIndex = spinResult.winIndex ?? spinResult.rewards.indexOf(spinResult.amount);
                // Center of the segment (pointer is at 0deg/top)
                const segmentCenter = winIndex * anglePerSegment + anglePerSegment / 2;
                // Add a small random offset within the segment for realism
                const minOffset = -anglePerSegment / 4, maxOffset = anglePerSegment / 4;
                const randomOffset = Math.random() * (maxOffset - minOffset) + minOffset;
                // Final angle to rotate so that the winning segment's center aligns with the pointer
                const targetAngle = 360 * 5 + (360 - (segmentCenter + randomOffset));

                canvas.style.transition = 'transform 4s cubic-bezier(0.33, 1, 0.68, 1)';
                canvas.style.transform = `rotate(${targetAngle}deg)`;

                setTimeout(() => {
                    isSpinning = false;
                    spinBtn.disabled = false;
                    canvas.style.transition = 'none';
                    canvas.style.transform = `rotate(${targetAngle % 360}deg)`;

                    // Show pop-up with win amount
                    const winModal = new bootstrap.Modal(document.getElementById('winModal'));
                    document.getElementById('winMessage').innerText = `You won Ksh ${spinResult.amount}!`;
                    winModal.show();

                    // Refresh page after closing modal to update eligibility
                    document.getElementById('winModal').addEventListener('hidden.bs.modal', function() {
                        location.reload();
                    }, { once: true });
                }, 4000);
            })
            .catch(err => {
                isSpinning = false;
                spinBtn.disabled = false;
                alert('Error processing spin: ' + err.message);
            });
        });
    </script>
</body>
</html>