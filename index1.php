<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$error = '';

try {
    // Fetch user data
    $stmt = $conn->prepare('SELECT full_name, username, referral_code FROM users WHERE user_id = ?');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Fetch wallet balance
    $stmt = $conn->prepare('SELECT balance FROM wallet WHERE user_id = ?');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $wallet = $stmt->get_result()->fetch_assoc();
    $balance = $wallet ? number_format($wallet['balance'], 2) : '0.00';
    $stmt->close();

    // Fetch points (handle missing table)
    $points = 0;
    $stmt = $conn->prepare('SELECT points FROM points WHERE user_id = ?');
    if ($stmt !== false) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $points_data = $stmt->get_result()->fetch_assoc();
        $points = $points_data ? $points_data['points'] : 0;
        $stmt->close();
    }

    // Count referrals
    $referral_count = 0;
    $stmt = $conn->prepare('SELECT COUNT(*) as referral_count FROM users WHERE referred_by = ?');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('s', $user['referral_code']);
    $stmt->execute();
    $referral_count = $stmt->get_result()->fetch_assoc()['referral_count'];
    $stmt->close();

    // Fetch recent activities (handle missing table)
    $activities = [];
    $stmt = $conn->prepare('
        SELECT description, amount, created_at 
        FROM transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ');
    if ($stmt !== false) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // Fetch recent referrals (for highlights)
    $recent_referrals = [];
    $stmt = $conn->prepare('
        SELECT full_name, created_at 
        FROM users 
        WHERE referred_by = ? 
        ORDER BY created_at DESC 
        LIMIT 3
    ');
    if ($stmt !== false) {
        $stmt->bind_param('s', $user['referral_code']);
        $stmt->execute();
        $recent_referrals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} catch (Exception $e) {
    $error = 'Error: ' . htmlspecialchars($e->getMessage());
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

    <!-- Desktop Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h2>LOOMA</h2>
            <p>Earn While You Play</p>
        </div>
        <nav class="nav flex-column">
            <a href="index1.php" class="nav-link active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="games.php" class="nav-link">
                <i class="fas fa-gamepad"></i>
                <span>Games</span>
            </a>
            <a href="questions.php" class="nav-link">
                <i class="fas fa-book"></i>
                <span>Quizes</span>
            </a>
            <a href="wallet1.php" class="nav-link">
                <i class="fas fa-chart-line"></i>
                <span>Earnings</span>
            </a>
            <a href="referrals.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Referrals</span>
            </a>
            <a href="achievements.php" class="nav-link">
                <i class="fas fa-trophy"></i>
                <span>Leaderboard</span>
            </a>
            <a href="settings.php" class="nav-link">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Log out</span>
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

        <!-- Content Container -->
        <div class="content-container">
            <?php if ($error): ?>
                <div class="alert alert-danger animate-fadeIn">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Dashboard Stats -->
            <div class="row animate-fadeIn">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-icon primary">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="card-value"><?php echo htmlspecialchars($points); ?></div>
                        <div class="card-title">Points Earned</div>
                        <a href="wallet1.php" class="btn btn-sm btn-outline-primary">View History</a>
                    </div>
                </div>
                <div class="col-md-4 delay-1">
                    <div class="dashboard-card">
                        <div class="card-icon success">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="card-value">Ksh<?php echo htmlspecialchars($balance); ?></div>
                        <div class="card-title">Available Balance</div>
                        <a href="wallet1.php" class="btn btn-sm btn-outline-success">Withdraw Now</a>
                    </div>
                </div>
                <div class="col-md-4 delay-2">
                    <div class="dashboard-card">
                        <div class="card-icon accent">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-value"><?php echo htmlspecialchars($referral_count); ?></div>
                        <div class="card-title">Referrals</div>
                        <a href="referrals.php" class="btn btn-sm btn-outline-danger">Invite Friends</a>
                    </div>
                </div>
            </div>

            <!-- Welcome Section -->
            <div class="card mt-4 animate-fadeIn">
                <div class="card-body">
                    <h3 class="card-title">Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h3>
                    <p>Dive into Looma’s world of fun and rewards! Play games, take quizzes, invite friends, and watch your earnings grow with instant M-Pesa withdrawals.</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4 animate-fadeIn">
                <div class="card-body">
                    <h3 class="card-title">Quick Actions</h3>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="games.php" class="btn btn-primary w-100">
                                <i class="fas fa-gamepad me-2"></i>Play Games
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="questions.php" class="btn btn-primary w-100">
                                <i class="fas fa-book me-2"></i>Take Quizes
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="referrals.php" class="btn btn-primary w-100">
                                <i class="fas fa-users me-2"></i>Invite Friends
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="card mt-4 animate-fadeIn">
                <div class="card-body">
                    <h3 class="card-title">Recent Activity</h3>
                    <?php if (empty($activities)): ?>
                        <p>No recent activity yet. Start playing games or quizzes to see your progress!</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activities as $activity): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                            <td class="<?php echo $activity['amount'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $activity['amount'] >= 0 ? '+Ksh' : '-Ksh'; ?>
                                                <?php echo number_format(abs($activity['amount']), 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Game Progress (Placeholder) -->
            <div class="card mt-4 animate-fadeIn">
                <div class="card-body">
                    <h3 class="card-title">Game Progress</h3>
                    <p>Track your achievements in Looma’s games and quizzes!</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="progress-card">
                                <h5>Trivia Challenge</h5>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 60%;" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">60%</div>
                                </div>
                                <p class="mt-2">Completed 12/20 levels</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="progress-card">
                                <h5>Quiz Master</h5>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 45%;" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100">45%</div>
                                </div>
                                <p class="mt-2">Answered 9/20 questions</p>
                            </div>
                        </div>
                    </div>
                    <a href="games.php" class="btn btn-outline-primary">Explore More Games</a>
                </div>
            </div>

            <!-- Referral Highlights -->
            <div class="card mt-4 animate-fadeIn">
                <div class="card-body">
                    <h3 class="card-title">Referral Highlights</h3>
                    <?php if (empty($recent_referrals)): ?>
                        <p>Invite friends to join Looma and see them here!</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($recent_referrals as $referral): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="referral-card">
                                        <div class="referral-avatar bg-accent text-white d-flex align-items-center justify-content-center rounded-circle" style="width: 50px; height: 50px;">
                                            <?php
                                            $ref_initials = '';
                                            $ref_name_parts = explode(' ', $referral['full_name']);
                                            if (count($ref_name_parts) >= 1) {
                                                $ref_initials .= strtoupper(substr($ref_name_parts[0], 0, 1));
                                                if (count($ref_name_parts) > 1) {
                                                    $ref_initials .= strtoupper(substr($ref_name_parts[1], 0, 1));
                                                }
                                            }
                                            echo htmlspecialchars($ref_initials);
                                            ?>
                                        </div>
                                        <div class="ms-3">
                                            <h6><?php echo htmlspecialchars($referral['full_name']); ?></h6>
                                            <p class="text-muted small">Joined: <?php echo date('M d, Y', strtotime($referral['created_at'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <a href="referrals.php" class="btn btn-outline-danger">View All Referrals</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="index1.php" class="mobile-nav-item active">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="games.php" class="mobile-nav-item">
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

        // Run on load and resize
        window.addEventListener('resize', handleResize);
        document.addEventListener('DOMContentLoaded', handleResize);

        // Add animation classes as elements come into view
        const animateElements = document.querySelectorAll('.animate-fadeIn');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, { threshold: 0.1 });

        animateElements.forEach(element => {
            observer.observe(element);
        });
    </script>
</body>
</html>