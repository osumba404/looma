<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | Earnings</title>
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

    // Count referrals
    $stmt = $conn->prepare('SELECT COUNT(*) as referral_count FROM users WHERE referred_by = ?');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('s', $user['referral_code']);
    $stmt->execute();
    $referral_count = $stmt->get_result()->fetch_assoc()['referral_count'];
    $stmt->close();

    // Points (placeholder, no table)
    $points = 0; // Update if points table is added
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
            <a href="index1.php" class="nav-link">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="games.php" class="nav-link">
                <i class="fas fa-gamepad"></i>
                <span>Games</span>
            </a>
            <a href="wallet1.php" class="nav-link active">
                <i class="fas fa-chart-line"></i>
                <span>Earnings</span>
            </a>
            <a href="referrals.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Referrals</span>
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
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Wallet Stats -->
            <div class="row animate-fadeIn">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-icon primary">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="card-value"><?php echo htmlspecialchars($points); ?></div>
                        <div class="card-title">Points Earned</div>
                        <a href="#" class="btn btn-sm btn-outline-primary">View History</a>
                    </div>
                </div>
                <div class="col-md-4 delay-1">
                    <div class="dashboard-card">
                        <div class="card-icon success">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="card-value">Ksh<?php echo htmlspecialchars($balance); ?></div>
                        <div class="card-title">Available Balance</div>
                        <a href="#" class="btn btn-sm btn-outline-success">Withdraw Now</a>
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

            <!-- Wallet Overview -->
            <div class="card mt-4 animate-fadeIn">
                <div class="card-body">
                    <h3 class="card-title">Wallet Overview</h3>
                    <p>Your current balance is <strong>Ksh<?php echo htmlspecialchars($balance); ?></strong>. Below is a summary of your recent transactions.</p>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center">No transactions yet.</td>
                            </tr>
                            <!-- Add transaction data when table exists -->
                        </tbody>
                    </table>
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
        <a href="games.php" class="mobile-nav-item">
            <i class="fas fa-gamepad"></i>
            <span>Games</span>
        </a>
        <a href="wallet1.php" class="mobile-nav-item active">
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