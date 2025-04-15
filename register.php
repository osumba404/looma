<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Register for an account on Looma and start exploring rewards and games.">
    <title>Register for Looma</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        
    </style>
</head>
<body>
<?php
session_start();

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once 'includes/db.php';

$alert = '';
$msg_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];
    $terms = isset($_POST['terms']);

    // Validate inputs
    $errors = [];

    // Name validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $full_name)) {
        $errors[] = 'Name can only contain letters and spaces.';
    }

    // Username validation
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    }

    // Phone validation
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    } elseif (!preg_match('/^(\+254|0)[17]\d{8}$/', $phone)) {
        $errors[] = 'Invalid phone number format (e.g., +2547XXXXXXXX or 07XXXXXXXX).';
    }

    // Email validation
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    // Password validation
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must include at least one uppercase letter, one number, and one special character.';
    }

    // Confirm password and terms
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$terms) {
        $errors[] = 'You must agree to the terms and conditions.';
    }

    // Normalize phone
    $normalized_phone = empty($errors) && (substr($phone, 0, 1) === '0') ? '+254' . substr($phone, 1) : $phone;

    // Check for existing username/phone/email
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR phone = ? OR email = ?');
            if ($stmt === false) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param('sss', $username, $normalized_phone, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_row()[0];
            $stmt->close();

            if ($count > 0) {
                $errors[] = 'Username, phone, or email already registered.';
            }
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
    }

    // Process if no errors
    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $referral_code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $is_verified = 1; // No phone verification

            // Insert user
            $stmt = $conn->prepare('INSERT INTO users (full_name, username, phone, email, password, referral_code, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?)');
            if ($stmt === false) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param('ssssssi', $full_name, $username, $normalized_phone, $email, $hashed_password, $referral_code, $is_verified);
            $stmt->execute();
            $user_id = $conn->insert_id;
            $stmt->close();

            // Insert wallet
            $stmt = $conn->prepare('INSERT INTO wallet (user_id, balance) VALUES (?, 0.00)');
            if ($stmt === false) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $_SESSION['user_id'] = $user_id;
            $_SESSION['full_name'] = $full_name;
            header('Location: index1.php');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $alert = 'Registration failed: ' . htmlspecialchars($e->getMessage());
            $msg_class = 'error';
        }
    } else {
        $alert = implode('<br>', $errors);
        $msg_class = 'error';
    }
}
?>

    <main>
        <div class="form-card">
            <h2>Create Your Account</h2>
            <?php if ($alert): ?>
                <div class="alert alert-<?php echo $msg_class === 'success' ? 'success' : 'danger'; ?>">
                    <i class="fas fa-<?php echo $msg_class === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $alert; ?>
                </div>
            <?php endif; ?>
            <form method="POST" id="registrationForm">
                <div class="form-group">
                    <label for="full_name">Full Name:</label>
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Enter your full name" required aria-label="Full Name" autocomplete="name">
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <i class="fas fa-at input-icon"></i>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username" required aria-label="Username" autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <i class="fas fa-phone input-icon"></i>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="e.g., +2547XXXXXXXX" required pattern="(\+254|0)[17]\d{8}" aria-label="Phone Number">
                </div>
                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email address" required aria-label="Email Address" autocomplete="email">
                </div>
                <div class="form-group password-container">
                    <label for="password">Password:</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Create a strong password" required aria-label="Password" autocomplete="new-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <div class="password-strength">
                        <div class="password-strength-meter" id="passwordStrengthMeter"></div>
                    </div>
                    <div class="password-feedback" id="passwordFeedback"></div>
                    <div class="requirements">
                        Password requirements:
                        <ul>
                            <li>At least 8 characters</li>
                            <li>At least one uppercase letter</li>
                            <li>At least one number</li>
                            <li>At least one special character</li>
                        </ul>
                    </div>
                </div>
                <div class="form-group password-container">
                    <label for="confirm-password">Confirm Password:</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="confirm-password" name="confirm-password" class="form-control" placeholder="Confirm your password" required aria-label="Confirm Password" autocomplete="new-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm-password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="terms">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
                </div>
                <button type="submit" class="btn btn-primary" aria-label="Register">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            <p class="login-link mt-3">Already have an account? <a href="login.php">Login Here</a></p>
        </div>
    </main>
    <footer>
        <p>© <?php echo date('Y'); ?> Looma. All rights reserved.</p>
        <p><a href="index.php">Home</a> | <a href="login.php">Login</a> | <a href="terms.php">Terms</a> | <a href="privacy.php">Privacy</a></p>
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

        const passwordInput = document.getElementById('password');
        const meter = document.getElementById('passwordStrengthMeter');
        const feedback = document.getElementById('passwordFeedback');

        passwordInput.addEventListener('input', updatePasswordStrength);

        function updatePasswordStrength() {
            const password = passwordInput.value;
            let strength = 0;
            let message = '';
            if (password.length >= 8) strength += 25;
            if (password.match(/[A-Z]/)) strength += 25;
            if (password.match(/[0-9]/)) strength += 25;
            if (password.match(/[^A-Za-z0-9]/)) strength += 25;
            meter.style.width = strength + '%';
            if (strength <= 25) {
                meter.style.background = '#e63946';
                message = 'Weak password';
            } else if (strength <= 50) {
                meter.style.background = '#f4a261';
                message = 'Fair password';
            } else if (strength <= 75) {
                meter.style.background = '#90be6d';
                message = 'Good password';
            } else {
                meter.style.background = '#2e7d32';
                message = 'Strong password';
            }
            feedback.textContent = message;
        }

        const confirmPassword = document.getElementById('confirm-password');
        const registrationForm = document.getElementById('registrationForm');
        registrationForm.addEventListener('submit', function(event) {
            if (passwordInput.value !== confirmPassword.value) {
                event.preventDefault();
                feedback.textContent = 'Passwords do not match!';
                feedback.style.color = '#e63946';
            }
        });
    </script>
</body>
</html>