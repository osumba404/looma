<!DOCTYPE html>
<html lang="en">
<head>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>

    </style>
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
                <div class="alert alert-danger">
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
                    <p>Your dashboard shows your progress on Looma. Play games, invite friends, and track your earnings.</p>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="card mt-4 animate-fadeIn">
                <div class="card-body">
                    <h3 class="card-title">Recent Activity</h3>
                    <?php if (empty($activities)): ?>
                        <p>No recent activity yet. Start playing to see your progress!</p>
                    <?php else: ?>
                        <table class="table table-striped">
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
                                        <td><?php echo date('M d, Y', strtotime($activity['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                        <td><?php echo $activity['amount'] >= 0 ? '+Ksh' : '-Ksh'; ?><?php echo number_format(abs($activity['amount']), 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
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