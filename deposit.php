<?php
session_start();
require_once 'includes/db.php';
require 'vendor/autoload.php'; // Composer autoload for phpdotenv
use Dotenv\Dotenv;

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize variables
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Unknown';
$csrf_token = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32)); // Use existing session token or generate new
$_SESSION['csrf_token'] = $csrf_token;
$user = null;
$initials = '';
$error = ''; // Initialize $error to avoid undefined variable warning

try {
    // Fetch user data
    $stmt = $conn->prepare('SELECT full_name, username, phone FROM users WHERE user_id = ?');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        session_destroy();
        header('Location: login.php?error=User not found');
        exit();
    }

    // Get user initials
    $name_parts = explode(' ', $user['full_name']);
    if (count($name_parts) >= 1) {
        $initials .= strtoupper(substr($name_parts[0], 0, 1));
        if (count($name_parts) > 1) {
            $initials .= strtoupper(substr($name_parts[1], 0, 1));
        }
    }
} catch (Exception $e) {
    error_log('Error in deposit.php: ' . $e->getMessage());
    $error = '<script>alert("' . htmlspecialchars($e->getMessage()) . '");</script>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | Deposit Funds</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .dashboard-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .card-icon.success { color: #00cec9; }
        .deposit-form {
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .animate-fadeIn {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }
        .animate-fadeIn.animate {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 576px) {
            .deposit-form {
                padding: 15px;
            }
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
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="games.php" class="nav-link">
                <i class="fas fa-gamepad"></i>
                <span>Games</span>
            </a>
            <a href="wallet1.php" class="nav-link">
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
                    <div class="fw-bold"><?php echo htmlspecialchars($user['username'] ?? 'Unknown'); ?></div>
                </div>
            </div>
        </div>

        <!-- Content Container -->
        <div class="content-container">
            <?php echo $error; ?>

            <div class="row justify-content-center animate-fadeIn">
                <div class="col-md-6">
                    <div class="dashboard-card">
                        <div class="card-icon success">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <h3 class="card-title">Deposit Funds</h3>
                        <form id="depositForm" class="deposit-form" method="POST" action="wallet1.php">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="deposit" value="1">
                            <div class="mb-3">
                                <label for="deposit_amount" class="form-label">Amount (Ksh)</label>
                                <input type="number" class="form-control" id="deposit_amount" name="amount" min="100" step="0.01" required>
                                <small class="form-text text-muted">Minimum deposit: Ksh 100</small>
                            </div>
                            <div class="mb-3">
                                <label for="deposit_phone" class="form-label">M-Pesa Phone Number</label>
                                <input type="text" class="form-control" id="deposit_phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" readonly required>
                                <small class="form-text text-muted">Phone number from your account</small>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Submit Deposit</button>
                        </form>
                    </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js?v=1.0"></script>
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

        // Animation observer
        const animateElements = document.querySelectorAll('.animate-fadeIn');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, { threshold: 0.1 });
        animateElements.forEach(element => observer.observe(element));

        // Handle form submission with AJAX
        document.getElementById('depositForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const submitButton = e.target.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            try {
                const response = await fetch('wallet1.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    setTimeout(() => window.location.href = 'wallet1.php', 1000);
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('An error occurred while processing your request: ' + error.message);
            } finally {
                submitButton.disabled = false;
            }
        });
    </script>
</body>
</html>