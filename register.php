<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Looma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php
session_start();
require_once 'includes/db.php';

$alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($full_name) || empty($username) || empty($phone) || empty($password)) {
        $alert = '<div class="alert alert-danger">Please fill in all required fields.</div>';
    } elseif (strlen($username) < 3) {
        $alert = '<div class="alert alert-danger">Username must be at least 3 characters.</div>';
    } elseif (!preg_match('/^(\+254|0)[17]\d{8}$/', $phone)) {
        $alert = '<div class="alert alert-danger">Invalid phone number format.</div>';
    } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alert = '<div class="alert alert-danger">Invalid email format.</div>';
    } elseif (strlen($password) < 6) {
        $alert = '<div class="alert alert-danger">Password must be at least 6 characters.</div>';
    } else {
        $normalized_phone = (substr($phone, 0, 1) === '0') ? '+254' . substr($phone, 1) : $phone;

        // Check for duplicates
        $stmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR phone = ? OR email = ?');
        if ($stmt === false) {
            $alert = '<div class="alert alert-danger">Database error: ' . htmlspecialchars($conn->error) . '</div>';
        } else {
            $email_param = $email ?: null;
            $stmt->bind_param('sss', $username, $normalized_phone, $email_param);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_row()[0];
            $stmt->close();

            if ($count > 0) {
                $alert = '<div class="alert alert-danger">Username, phone, or email already exists.</div>';
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $referral_code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
                $verification_code = sprintf("%06d", mt_rand(0, 999999));

                $conn->begin_transaction();
                try {
                    // Insert user
                    $stmt = $conn->prepare('INSERT INTO users (full_name, username, phone, email, password, referral_code) VALUES (?, ?, ?, ?, ?, ?)');
                    if ($stmt === false) {
                        throw new Exception('Prepare failed: ' . $conn->error);
                    }
                    $stmt->bind_param('ssssss', $full_name, $username, $normalized_phone, $email_param, $password_hash, $referral_code);
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

                    /* Insert verification code
                    $stmt = $conn->prepare('INSERT INTO verification_codes (user_id, code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))');
                    if ($stmt === false) {
                        throw new Exception('Prepare failed: ' . $conn->error);
                    }
                    $stmt->bind_param('is', $user_id, $verification_code);
                    $stmt->execute();
                    $stmt->close();

                    $conn->commit(); */

                    // Simulate SMS
                    $_SESSION['user_id'] = $user_id;
                    header('Location: verify.php');
                    exit;
                } catch (Exception $e) {
                    $conn->rollback();
                    $alert = '<div class="alert alert-danger">Server error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
        }
    }
}
?>

    <div class="container">
        <div class="form-card">
            <h2>Create Your Account</h2>
            <p class="text-center text-muted mb-4">Join now to start exploring!</p>
            <?php echo $alert; ?>
            <form id="register-form" method="POST">
                <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
                <input type="text" name="username" class="form-control" placeholder="Username" required minlength="3">
                <input type="tel" name="phone" class="form-control" placeholder="Phone (e.g., +2547XXXXXXXX)" required pattern="(\+254|0)[17]\d{8}">
                <input type="email" name="email" class="form-control" placeholder="Email (optional)">
                <input type="password" name="password" class="form-control" placeholder="Password" required minlength="6">
                <button type="submit" class="btn btn-primary" id="register-submit">
                    Sign Up
                    <span class="spinner spinner-border spinner-border-sm"></span>
                </button>
            </form>
            <p class="text-center mt-3">
                Already have an account? <a href="login.php">Log In</a>
            </p>
        </div>
    </div>
    <footer>
        <p>© 2025 Looma. All rights reserved.</p>
        <p><a href="index.php">Home</a> | <a href="login.php">Log In</a> | <a href="#">Terms</a> | <a href="#">Privacy</a></p>
    </footer>
    <script>
        // Form Validation and Submission Handling
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

        document.getElementById('register-form').addEventListener('submit', function(event) {
            const fullName = document.querySelector('input[name="full_name"]').value;
            const username = document.querySelector('input[name="username"]').value;
            const phone = document.querySelector('input[name="phone"]').value;
            const password = document.querySelector('input[name="password"]').value;
            const button = document.getElementById('register-submit');

            if (!fullName || !username || !phone || !password) {
                event.preventDefault();
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger';
                alert.textContent = 'Please fill in all required fields.';
                document.querySelector('.form-card').prepend(alert);
                setTimeout(() => alert.remove(), 5000);
                return;
            }

            showSpinner(button, true);
            setTimeout(() => showSpinner(button, false), 1000);
        });
    </script>
</body>
</html>