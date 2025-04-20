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
$full_name = $_SESSION['full_name'];
$error = '';

// Fetch user data
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
    $error = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Fetch spin data
$spin_summary = ['registration' => false, 'weekly' => false, 'bet' => false];
$spin_rewards = [];
$total_spin_points = 0;

try {
    // Check available spins (e.g., has the user used their registration spin?)
    $stmt = $conn->prepare('SELECT spin_type, COUNT(*) as count FROM spins WHERE user_id = ? GROUP BY spin_type');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $spin_summary[$row['spin_type']] = $row['count'] > 0;
    }
    $stmt->close();

    // Fetch recent spin rewards
    $stmt = $conn->prepare('SELECT reward, created_at FROM spin_rewards WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $spin_rewards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch total points earned from spins
    $stmt = $conn->prepare('SELECT SUM(points_earned) as total_points FROM user_game_history WHERE user_id = ? AND game_type = "spin"');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_spin_points = $result->fetch_assoc()['total_points'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    $error .= '<div class="alert alert-danger">Error fetching spin data: ' . htmlspecialchars($e->getMessage()) . '</div>';
}




// Fetch Word Scramble data
$scramble_games_played = 0;
$scramble_rewards = [];
$total_scramble_points = 0;

try {
    // Fetch total games played for Word Scramble
    $stmt = $conn->prepare('SELECT COUNT(*) as count FROM user_game_history WHERE user_id = ? AND game_type = "word_scramble"');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $scramble_games_played = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();

    // Fetch recent Word Scramble rewards
    $stmt = $conn->prepare('SELECT reward, created_at FROM scramble_rewards WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $scramble_rewards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch total points earned from Word Scramble
    $stmt = $conn->prepare('SELECT SUM(points_earned) as total_points FROM user_game_history WHERE user_id = ? AND game_type = "word_scramble"');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_scramble_points = $result->fetch_assoc()['total_points'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    $error .= '<div class="alert alert-danger">Error fetching Word Scramble data: ' . htmlspecialchars($e->getMessage()) . '</div>';
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

// Mock game progress (unchanged)
$game_progress = [
    ['name' => 'Memory Match', 'level' => 2, 'points' => 85, 'max_points' => 100, 'progress' => 85],
    ['name' => 'Word Scramble', 'round' => 4, 'points' => 60, 'max_points' => 80, 'progress' => 75],
    ['name' => 'Math Blitz', 'questions' => 15, 'points' => 75, 'max_points' => 90, 'progress' => 83],
    ['name' => 'Daily Challenge', 'completed' => true, 'points' => 50, 'max_points' => 50, 'progress' => 100]
];

// Mock achievements (unchanged)
$achievements = [
    ['title' => 'First Win', 'description' => 'Won your first game', 'icon' => 'fa-medal', 'date' => '2025-04-10'],
    ['title' => 'Quick Learner', 'description' => 'Completed 5 games', 'icon' => 'fa-star', 'date' => '2025-04-12'],
    ['title' => 'High Scorer', 'description' => 'Scored above 80 points', 'icon' => 'fa-trophy', 'date' => '2025-04-13'],
    ['title' => 'Streak Master', 'description' => '3-day play streak', 'icon' => 'fa-crown', 'date' => '2025-04-15']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | Games</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .games-section, .game-progress, .achievements {
            padding: 2rem 0;
        }
        .card {
            border-radius: 10px;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-img-top {
            height: 150px;
            object-fit: cover;
        }
        .progress {
            height: 20px;
        }
        .achievement-card i {
            color: #007bff;
        }
        .btn-primary {
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .spin-details {
            font-size: 0.9rem;
        }
        .scramble-details {
    font-size: 0.9rem;
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

        <!-- Games Section -->
        <section class="games-section py-4 animate-fadeIn">
            <div class="container">
                <?php if ($error): ?>
                    <?php echo $error; ?>
                <?php endif; ?>
                <h2 class="mb-4">Featured Games</h2>
                <div class="row g-4">
                    <!-- Other game cards (unchanged) -->
                    <div class="col-md-4">
                        <div class="card">
                            <img src="https://via.placeholder.com/300x150?text=Memory+Match" class="card-img-top" alt="Memory Match">
                            <div class="card-body">
                                <h5 class="card-title">Memory Match</h5>
                                <p class="card-text">Flip cards to find matching pairs and boost your memory skills.</p>
                                <p class="card-text"><small class="text-muted">3 Levels • 10 Mins • 100 Points</small></p>
                                <a href="#" class="btn btn-primary w-100">Play Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card scramble-card">
                            <img src="https://via.placeholder.com/300x150?text=Word+Scramble" class="card-img-top" alt="Word Scramble">
                            <div class="card-body">
                                <h5 class="card-title">Word Scramble</h5>
                                <p class="card-text">Unscramble letters to form words and earn rewards.</p>
                                <div class="scramble-details">
                                    <p><strong>Games Played:</strong> <?php echo htmlspecialchars($scramble_games_played); ?></p>
                                    <?php if (!empty($scramble_rewards)): ?>
                                        <p><strong>Recent Rewards:</strong></p>
                                        <ul>
                                            <?php foreach ($scramble_rewards as $reward): ?>
                                                <li>Ksh <?php echo htmlspecialchars($reward['reward']); ?> on <?php echo date('M d, Y', strtotime($reward['created_at'])); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <p><strong>Total Scramble Points:</strong> <?php echo htmlspecialchars($total_scramble_points); ?></p>
                                </div>
                                <p class="card-text"><small class="text-muted">3 Variations • 5-10 Mins • Up to 150 Ksh</small></p>
                                <a href="active-game.php?game=scramble" class="btn btn-primary w-100">Play Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <img src="https://via.placeholder.com/300x150?text=Math+Blitz" class="card-img-top" alt="Math Blitz">
                            <div class="card-body">
                                <h5 class="card-title">Math Blitz</h5>
                                <p class="card-text">Solve quick math problems to rack up points.</p>
                                <p class="card-text"><small class="text-muted">20 Questions • 5 Mins • 90 Points</small></p>
                                <a href="#" class="btn btn-primary w-100">Play Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <img src="https://via.placeholder.com/300x150?text=Daily+Challenge" class="card-img-top" alt="Daily Challenge">
                            <div class="card-body">
                                <h5 class="card-title">History Challenge</h5>
                                <p class="card-text">A new mini-game every day for bonus points.</p>
                                <p class="card-text"><small class="text-muted">1 Round • 5 Mins • 50 Points</small></p>
                                <a href="#" class="btn btn-primary w-100">Play Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <img src="https://via.placeholder.com/300x150?text=Trivia+Dash" class="card-img-top" alt="Trivia Dash">
                            <div class="card-body">
                                <h5 class="card-title">Geography Trivia</h5>
                                <p class="card-text">Answer Geo and Environment related Questions.</p>
                                <p class="card-text"><small class="text-muted">10 Questions • 7 Mins • 70 Points</small></p>
                                <a href="#" class="btn btn-primary w-100">Play Now</a>
                            </div>
                        </div>
                    </div>

                    <!-- Updated Spin & Earn Card -->
                    <div class="col-md-4">
                        <div class="card spin-card">
                            <img src="https://via.placeholder.com/300x150?text=Spin+%26+Earn" class="card-img-top" alt="Spin & Earn">
                            <div class="card-body">
                                <h5 class="card-title">Spin & Earn</h5>
                                <p class="card-text">Spin the wheel to win exciting rewards!</p>
                                <div class="spin-details">
                                    <?php if (!$spin_summary['registration']): ?>
                                        <p><strong>Registration Spin:</strong> Available! Win up to Ksh 250.</p>
                                    <?php else: ?>
                                        <p><strong>Registration Spin:</strong> Already used.</p>
                                    <?php endif; ?>
                                    <?php if (!$spin_summary['weekly']): ?>
                                        <p><strong>Weekly Spin:</strong> Available! Win up to Ksh 500.</p>
                                    <?php else: ?>
                                        <p><strong>Weekly Spin:</strong> Used this week.</p>
                                    <?php endif; ?>
                                    <p><strong>Bet Spin:</strong> Stake Ksh 100-1,000 for up to 600% profit.</p>
                                    <?php if (!empty($spin_rewards)): ?>
                                        <p><strong>Recent Rewards:</strong></p>
                                        <ul>
                                            <?php foreach ($spin_rewards as $reward): ?>
                                                <li>Ksh <?php echo htmlspecialchars($reward['reward']); ?> on <?php echo date('M d, Y', strtotime($reward['created_at'])); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <p><strong>Total Spin Points:</strong> <?php echo htmlspecialchars($total_spin_points); ?></p>
                                </div>
                                <p class="card-text"><small class="text-muted">Luck-Based • Varies • Up to 600% Profit</small></p>
                                <a href="active-game.php?game=spin" class="btn btn-primary w-100">Play Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Game Progress (unchanged) -->
        <section class="game-progress py-4 animate-fadeIn">
            <div class="container">
                <h2 class="mb-4">Your Game Progress</h2>
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($game_progress)): ?>
                            <p>Start playing games to track your progress!</p>
                        <?php else: ?>
                            <div class="row sheezy
                            <div class="row">
                                <?php foreach ($game_progress as $game): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="progress-card">
                                            <h5><?php echo htmlspecialchars($game['name']); ?></h5>
                                            <p class="text-muted">
                                                <?php
                                                if (isset($game['level'])) {
                                                    echo 'Level ' . $game['level'];
                                                } elseif (isset($game['round'])) {
                                                    echo 'Round ' . $game['round'];
                                                } elseif (isset($game['questions'])) {
                                                    echo $game['questions'] . ' Questions';
                                                } else {
                                                    echo 'Completed';
                                                }
                                                ?>
                                                - <?php echo $game['points'] . '/' . $game['max_points']; ?> Points
                                            </p>
                                            <div class="progress">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $game['progress']; ?>%;" aria-valuenow="<?php echo $game['progress']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $game['progress']; ?>%
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Achievements (unchanged) -->
        <section class="achievements py-4 animate-fadeIn">
            <div class="container">
                <h2 class="mb-4">Recent Achievements</h2>
                <div class="row g-4">
                    <?php if (empty($achievements)): ?>
                        <div class="col-12">
                            <p>Earn achievements by playing games and completing challenges!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($achievements as $achievement): ?>
                            <div class="col-md-3">
                                <div class="card achievement-card text-center">
                                    <div class="card-body">
                                        <i class="fas <?php echo htmlspecialchars($achievement['icon']); ?> fa-2x mb-2"></i>
                                        <h5 class="card-title"><?php echo htmlspecialchars($achievement['title']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($achievement['description']); ?></p>
                                        <p class="card-text"><small class="text-muted">Earned: <?php echo date('M d, Y', strtotime($achievement['date'])); ?></small></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="text-center mt-4">
                    <a href="achievements.php" class="btn btn-outline-primary">View All Achievements</a>
                </div>
            </div>
        </section>
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


        // Add animation classes as elements come into view (unchanged)
        const animateElements = document.querySelectorAll('.animate-fadeIn');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fadeIn');
                }
            });
        }, { threshold: 0.1 });

        animateElements.forEach(element => {
            observer.observe(element);
        });
    </script>
</body>
</html>