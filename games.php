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
    $stmt = $conn->prepare('SELECT spin_type, COUNT(*) as count FROM spins WHERE user_id = ? GROUP BY spin_type');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $spin_summary[$row['spin_type']] = $row['count'] > 0;
    }
    $stmt->close();

    $stmt = $conn->prepare('SELECT reward, created_at FROM spin_rewards WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $spin_rewards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

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
    $stmt = $conn->prepare('SELECT COUNT(*) as count FROM user_game_history WHERE user_id = ? AND game_type = "word_scramble"');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $scramble_games_played = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();

    $stmt = $conn->prepare('SELECT reward, created_at FROM scramble_rewards WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $scramble_rewards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('SELECT SUM(points_earned) as total_points FROM user_game_history WHERE user_id = ? AND game_type = "word_scramble"');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_scramble_points = $result->fetch_assoc()['total_points'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    $error .= '<div class="alert alert-danger">Error fetching Word Scramble data: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Fetch Memory Match data
$memory_games_played = 0;
$memory_rewards = [];
$total_memory_points = 0;

try {
    $stmt = $conn->prepare('SELECT COUNT(*) as count FROM user_game_history WHERE user_id = ? AND game_type = "memory_match"');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $memory_games_played = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();

    $stmt = $conn->prepare('SELECT reward, created_at FROM game_rewards WHERE user_id = ? AND game_type = "memory_match" ORDER BY created_at DESC LIMIT 3');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $memory_rewards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('SELECT SUM(points_earned) as total_points FROM user_game_history WHERE user_id = ? AND game_type = "memory_match"');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_memory_points = $result->fetch_assoc()['total_points'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    $error .= '<div class="alert alert-danger">Error fetching Memory Match data: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Fetch Math Blitz data
$math_games_played = 0;
$math_rewards = [];
$total_math_points = 0;

try {
    $stmt = $conn->prepare('SELECT COUNT(*) as count FROM user_game_history WHERE user_id = ? AND game_type = "math_blitz"');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $math_games_played = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();

    $stmt = $conn->prepare('SELECT reward, created_at FROM game_rewards WHERE user_id = ? AND game_type = "math_blitz" ORDER BY created_at DESC LIMIT 3');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $math_rewards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('SELECT SUM(points_earned) as total_points FROM user_game_history WHERE user_id = ? AND game_type = "math_blitz"');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_math_points = $result->fetch_assoc()['total_points'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    $error .= '<div class="alert alert-danger">Error fetching Math Blitz data: ' . htmlspecialchars($e->getMessage()) . '</div>';
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

// Prepare game progress data from database
$game_progress = [
    [
        'name' => 'Memory Match',
        'games_played' => $memory_games_played,
        'points' => $total_memory_points,
        'max_points' => 100,
        'progress' => min(100, round($total_memory_points / 100 * 100))
    ],
    [
        'name' => 'Math Blitz',
        'games_played' => $math_games_played,
        'points' => $total_math_points,
        'max_points' => 100,
        'progress' => min(100, round($total_math_points / 100 * 100))
    ],
    [
        'name' => 'Word Scramble',
        'games_played' => $scramble_games_played,
        'points' => $total_scramble_points,
        'max_points' => 100,
        'progress' => min(100, round($total_scramble_points / 100 * 100))
    ],
    [
        'name' => 'Spin & Earn',
        'games_played' => $spin_summary['registration'] + $spin_summary['weekly'] + $spin_summary['bet'],
        'points' => $total_spin_points,
        'max_points' => 100,
        'progress' => min(100, round($total_spin_points / 100 * 100))
    ]
];

// Mock achievements
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
        .game-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        .game-card {
            flex: 1 1 calc(50% - 0.75rem);
            max-width: calc(50% - 0.75rem);
            height: 350px; /* Fixed height for uniformity */
            display: flex;
            flex-direction: column;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .game-card:hover {
            transform: translateY(-5px);
        }
        .game-card .card-img-top {
            height: 150px;
            width: 100%;
            object-fit: cover;
            object-position: center;
        }
        .game-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1rem;
        }
        .game-card .card-title {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        .game-card .card-text {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            flex-grow: 1;
        }
        .game-card .text-muted {
            font-size: 0.8rem;
        }
        .game-card .btn-primary {
            font-size: 0.9rem;
            padding: 0.5rem;
            transition: background-color 0.3s ease;
        }
        .game-card .btn-primary:hover {
            background-color: #0056b3;
        }
        .progress {
            height: 20px;
        }
        .achievement-card i {
            color: #007bff;
        }
        @media (max-width: 768px) {
            .game-card {
                flex: 1 1 100%;
                max-width: 100%;
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
                <div class="game-grid">
                    <!-- Memory Match -->
                    <div class="card game-card">
                        <img src="memory-game.jpg" class="card-img-top" alt="Memory Match">
                        <div class="card-body">
                            <h5 class="card-title">Memory Match</h5>
                            <p class="card-text">Flip cards to find matching pairs.</p>
                            <p class="card-text"><small class="text-muted">5-10 Mins • Up to 80 Ksh</small></p>
                            <a href="memory-game.php" class="btn btn-primary w-100">Play Now</a>
                        </div>
                    </div>
                    <!-- Math Blitz -->
                    <div class="card game-card">
                        <img src="math-blitz-game.jpg" class="card-img-top" alt="Math Blitz">
                        <div class="card-body">
                            <h5 class="card-title">Math Blitz</h5>
                            <p class="card-text">Solve 20 math problems fast.</p>
                            <p class="card-text"><small class="text-muted">2-5 Mins • Up to 80 Ksh</small></p>
                            <a href="math-blitz-game.php" class="btn btn-primary w-100">Play Now</a>
                        </div>
                    </div>
                    <!-- Word Scramble -->
                    <div class="card game-card">
                        <img src="word-scramble-game.png" class="card-img-top" alt="Word Scramble">
                        <div class="card-body">
                            <h5 class="card-title">Word Scramble</h5>
                            <p class="card-text">Unscramble letters to form words.</p>
                            <p class="card-text"><small class="text-muted">5-10 Mins • Up to 150 Ksh</small></p>
                            <a href="word-scramble-game.php" class="btn btn-primary w-100">Play Now</a>
                        </div>
                    </div>
                    <!-- Spin & Earn -->
                    <div class="card game-card">
                        <img src="spin-game.webp" class="card-img-top" alt="Spin & Earn">
                        <div class="card-body">
                            <h5 class="card-title">Spin & Earn</h5>
                            <p class="card-text">Spin the wheel for rewards.</p>
                            <p class="card-text"><small class="text-muted">Varies • Up to 600% Profit</small></p>
                            <a href="spin-game.php" class="btn btn-primary w-100">Play Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Game Progress -->
        <section class="game-progress py-4 animate-fadeIn">
            <div class="container">
                <h2 class="mb-4">Your Game Progress</h2>
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($game_progress) || array_sum(array_column($game_progress, 'games_played')) == 0): ?>
                            <p>Start playing games to track your progress!</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($game_progress as $game): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="progress-card">
                                            <h5><?php echo htmlspecialchars($game['name']); ?></h5>
                                            <p class="text-muted">
                                                Games Played: <?php echo $game['games_played']; ?> - 
                                                <?php echo $game['points'] . '/' . $game['max_points']; ?> Points
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

        <!-- Achievements -->
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
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            if (window.innerWidth > 992) {
                sidebar.classList.toggle('sidebar-collapsed');
                mainContent.classList.toggle('main-content-expanded');
            } else {
                sidebar.classList.toggle('active');
            }
        }

        function handleResize() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('active', 'sidebar-collapsed');
                mainContent.classList.remove('main-content-expanded');
            } else {
                sidebar.classList.remove('active');
                sidebar.classList.remove('sidebar-collapsed');
                mainContent.classList.remove('main-content-expanded');
            }
        }

        window.addEventListener('resize', handleResize);
        document.addEventListener('DOMContentLoaded', handleResize);

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