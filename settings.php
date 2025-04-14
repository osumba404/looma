<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Original inline styles (empty, preserved) */
    </style>
</head>
<body>
<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$profile_alert = '';
$password_alert = '';
$phone_alert = '';

// Fetch user data
$stmt = $pdo->prepare('SELECT full_name, username, email, phone FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($full_name) || empty($username)) {
        $profile_alert = '<div class="alert alert-danger">Full name and username are required.</div>';
    } elseif (strlen($username) < 3) {
        $profile_alert = '<div class="alert alert-danger">Username must be at least 3 characters.</div>';
    } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profile_alert = '<div class="alert alert-danger">Invalid email format.</div>';
    } else {
        // Check for duplicates
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND user_id != ?');
        $stmt->execute([$username, $email ?: null, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $profile_alert = '<div class="alert alert-danger">Username or email already exists.</div>';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET full_name = ?, username = ?, email = ? WHERE user_id = ?');
            if ($stmt->execute([$full_name, $username, $email ?: null, $user_id])) {
                $profile_alert = '<div class="alert alert-success">Profile updated successfully.</div>';
                $user['full_name'] = $full_name;
                $user['username'] = $username;
                $user['email'] = $email;
            } else {
                $profile_alert = '<div class="alert alert-danger">Failed to update profile.</div>';
            }
        }
    }
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
    } elseif (strlen($new_password) < 6) {
        $password_alert = '<div class="alert alert-danger">New password must be at least 6 characters.</div>';
    } else {
        $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ?');
        $stmt->execute([$user_id]);
        $stored_password = $stmt->fetchColumn();

        if (password_verify($current_password, $stored_password)) {
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
            if ($stmt->execute([$new_password_hash, $user_id])) {
                $password_alert = '<div class="alert alert-success">Password updated successfully.</div>';
            } else {
                $password_alert = '<div class="alert alert-danger">Failed to update password.</div>';
            }
        } else {
            $password_alert = '<div class="alert alert-danger">Current password is incorrect.</div>';
        }
    }
}

// Handle Phone Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_phone'])) {
    $new_phone = trim($_POST['phone'] ?? '');

    if (empty($new_phone)) {
        $phone_alert = '<div class="alert alert-danger">Phone number is required.</div>';
    } elseif (!preg_match('/^(\+254|0)[17]\d{8}$/', $new_phone)) {
        $phone_alert = '<div class="alert alert-danger">Invalid phone number format.</div>';
    } else {
        $normalized_phone = (substr($new_phone, 0, 1) === '0') ? '+254' . substr($new_phone, 1) : $new_phone;
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE phone = ? AND user_id != ?');
        $stmt->execute([$normalized_phone, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $phone_alert = '<div class="alert alert-danger">Phone number already exists.</div>';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET phone = ?, is_verified = FALSE WHERE user_id = ?');
            if ($stmt->execute([$normalized_phone, $user_id])) {
                $phone_alert = '<div class="alert alert-success">Phone updated. Please verify your new number.</div>';
                // Redirect to verify.php (not implemented here, just noted)
                $user['phone'] = $normalized_phone;
            } else {
                $phone_alert = '<div class="alert alert-danger">Failed to update phone.</div>';
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
            <a href="index.php" class="nav-link">
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
            <a href="wallet.php" class="nav-link">
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
                    <a class="nav-link active" data-bs-toggle="tab" href="#profile">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#account">Account</a>
                </li>
            </ul>
            
            <div class="tab-content mt-3">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Update Profile</h4>
                            <?php echo $profile_alert; ?>
                            <form id="profile-form" method="POST">
                                <input type="hidden" name="update_profile" value="1">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required minlength="3">
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email (Optional)</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly>
                                </div>
                                <button type="submit" class="btn btn-primary" id="profile-submit">
                                    Save Changes
                                    <span class="spinner spinner-border spinner-border-sm"></span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Account Tab -->
                <div class="tab-pane fade" id="account">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Account Settings</h4>
                            <!-- Change Password -->
                            <div class="mb-4">
                                <h5>Change Password</h5>
                                <?php echo $password_alert; ?>
                                <form id="password-form" method="POST">
                                    <input type="hidden" name="change_password" value="1">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required minlength="6">
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                    </div>
                                    <button type="submit" class="btn btn-primary" id="password-submit">
                                        Update Password
                                        <span class="spinner spinner-border spinner-border-sm"></span>
                                    </button>
                                </form>
                            </div>
                            <!-- Update Phone -->
                            <div>
                                <h5>Update Phone Number</h5>
                                <?php echo $phone_alert; ?>
                                <form id="phone-form" method="POST">
                                    <input type="hidden" name="update_phone" value="1">
                                    <div class="mb-3">
                                        <label for="new_phone" class="form-label">New Phone (e.g., +2547XXXXXXXX)</label>
                                        <input type="tel" class="form-control" id="new_phone" name="phone" required pattern="(\+254|0)[17]\d{8}">
                                    </div>
                                    <button type="submit" class="btn btn-primary" id="phone-submit">
                                        Update Phone
                                        <span class="spinner spinner-border spinner-border-sm"></span>
                                    </button>
                                    <small class="form-text text-muted">You’ll need to verify your new phone number.</small>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="index.php" class="mobile-nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="games.php" class="mobile-nav-item">
            <i class="fas fa-gamepad"></i>
            <span>Games</span>
        </a>
        <a href="wallet.php" class="mobile-nav-item">
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
            setTimeout(() => showSpinner(button, false), 1000); // Simulate submission
        });

        document.getElementById('profile-form').addEventListener('submit', function(event) {
            const button = document.getElementById('profile-submit');
            showSpinner(button, true);
            setTimeout(() => showSpinner(button, false), 1000);
        });

        document.getElementById('phone-form').addEventListener('submit', function(event) {
            const button = document.getElementById('phone-submit');
            showSpinner(button, true);
            setTimeout(() => showSpinner(button, false), 1000);
        });
    </script>
</body>
</html>