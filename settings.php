<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>

    </style>
</head>
<body>
<body>
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
$password_alert = '';

// Fetch user data
$stmt = $conn->prepare('SELECT full_name, username FROM users WHERE user_id = ?');
if ($stmt === false) {
    $password_alert = '<div class="alert alert-danger">Error: ' . htmlspecialchars($conn->error) . '</div>';
} else {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_alert = '<div class="alert alert-danger">All password fields are required.</div>';
    } elseif ($new_password !== $confirm_password) {
        $password_alert = '<div class="alert alert-danger">New passwords do not match.</div>';
    } elseif (strlen($new_password) < 8 || !preg_match('/[A-Z]/', $new_password) || 
             !preg_match('/[0-9]/', $new_password) || !preg_match('/[!@#$%^&*]/', $new_password)) {
        $password_alert = '<div class="alert alert-danger">Password must be at least 8 characters, include an uppercase letter, a number, and a special character.</div>';
    } else {
        $stmt = $conn->prepare('SELECT password FROM users WHERE user_id = ?');
        if ($stmt === false) {
            $password_alert = '<div class="alert alert-danger">Error: ' . htmlspecialchars($conn->error) . '</div>';
        } else {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stored_password = $stmt->get_result()->fetch_assoc()['password'];
            $stmt->close();

            if (password_verify($current_password, $stored_password)) {
                $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
                if ($stmt === false) {
                    $password_alert = '<div class="alert alert-danger">Error: ' . htmlspecialchars($conn->error) . '</div>';
                } else {
                    $stmt->bind_param('si', $new_password_hash, $user_id);
                    if ($stmt->execute()) {
                        $password_alert = '<div class="alert alert-success">Password updated successfully.</div>';
                    } else {
                        $password_alert = '<div class="alert alert-danger">Failed to update password.</div>';
                    }
                    $stmt->close();
                }
            } else {
                $password_alert = '<div class="alert alert-danger">Current password is incorrect.</div>';
            }
        }
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
            <a href="settings.php" class="nav-link active">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
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
                <div class="user-avatar"><?php echo htmlspecialchars(substr($user['full_name'], 0, 2)); ?></div>
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></div>
                </div>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="container">
            <h2 class="mb-4">Settings</h2>
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#account">Account</a>
                </li>
            </ul>

            <div class="tab-content mt-3">
                <!-- Account Tab -->
                <div class="tab-pane fade show active" id="account">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Change Password</h4>
                            <?php echo $password_alert; ?>
                            <form id="password-form" method="POST">
                                <input type="hidden" name="change_password" value="1">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required minlength="8">
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                </div>
                                <div class="mb-3">
                                    <small class="form-text text-muted">Password must be at least 8 characters, include an uppercase letter, a number, and a special character (!@#$%^&*).</small>
                                </div>
                                <button type="submit" class="btn btn-primary" id="password-submit">
                                    Update Password
                                    <span class="spinner spinner-border spinner-border-sm"></span>
                                </button>
                            </form>
                        </div>
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
        <a href="settings.php" class="mobile-nav-item active">
            <i class="fas fa-user"></i>
            <span>Account</span>
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

        // Form Validation and Submission Handling
        function showAlert(elementId, message, type) {
            const alert = document.getElementById(elementId);
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alert.style.display = 'block';
            setTimeout(() => (alert.style.display = 'none'), 5000);
        }

        function showSpinner(button, show) {
            const spinner = button.querySelector('.spinner');
            if (show) {
                button.disabled = true;
                spinner.style.display = 'inline-block';
            } else {
                button.disabled = false;
                spinner.style.display = 'none';
            }
        }

        document.getElementById('password-form').addEventListener('submit', function(event) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const button = document.getElementById('password-submit');

            if (newPassword !== confirmPassword) {
                event.preventDefault();
                showAlert('password-alert', 'Passwords do not match', 'danger');
                return;
            }

            showSpinner(button, true);
            setTimeout(() => showSpinner(button, false), 1000);
        });
    </script>
</body>
</html>