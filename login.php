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
    <style>

    </style>
</head>
<body>
<?php
session_start();

/* Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: wallet.php');
    exit();
}*/

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once 'includes/db.php';

$alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $login_input = filter_input(INPUT_POST, 'login_input', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember-me']);

    try {
        // Normalize phone number if it starts with '0'
        $normalized_input = $login_input;
        if (preg_match('/^0[17]\d{8}$/', $login_input)) {
            $normalized_input = '+254' . substr($login_input, 1);
        }

        // Check if user exists
        $stmt = $conn->prepare('SELECT user_id, full_name, username, phone, password FROM users WHERE username = ? OR phone = ?');
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param('ss', $login_input, $normalized_input);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];

                // Handle Remember Me
                if ($remember_me) {
                    $token = bin2hex(random_bytes(16));
                    setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                    // Note: Token not stored in DB (no token column)
                }

                header('Location: index1.php');
                exit;
            } else {
                $alert = 'Invalid password.';
            }
        } else {
            $alert = 'Username or phone not found.';
        }
    } catch (Exception $e) {
        $alert = 'Error: ' . htmlspecialchars($e->getMessage());
    }
}
?>

    <main>
        <div class="form-card">
            <h2>Welcome Back!</h2>
            <?php if ($alert): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($alert); ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="login_input">Username or Phone:</label>
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="login_input" name="login_input" class="form-control" placeholder="Enter username or phone" required aria-label="Username or Phone" autocomplete="username">
                </div>
                <div class="form-group password-container">
                    <label for="password">Password:</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required aria-label="Password" autocomplete="current-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="options-row">
                    <div class="remember-me">
                        <input type="checkbox" id="remember-me" name="remember-me">
                        <label for="remember-me">Remember me</label>
                    </div>
                    <div class="forgot-password">
                        <a href="forgot-password.php">Forgot Password?</a>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" aria-label="Login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            <div class="divider"><span>OR</span></div>
            <p class="signup-link">Don't have an account? <a href="register.php">Sign Up</a></p>
        </div>
    </main>
    <footer>
        <p>© <?php echo date('Y'); ?> Looma. All rights reserved.</p>
        <p><a href="index.php">Home</a> | <a href="login.php">Login</a> | <a href="register.php">Sign Up</a> | <a href="terms.php">Terms</a> | <a href="privacy.php">Privacy</a></p>
    </footer>
    <script>
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const toggle = input.nextElementSibling;
            const icon = toggle.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>