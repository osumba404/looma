<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Looma</title>
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
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $alert = '<div class="alert alert-danger">Please fill in all fields.</div>';
    } elseif (strlen($password) < 6) {
        $alert = '<div class="alert alert-danger">Password must be at least 6 characters.</div>';
    } else {
        try {
            $stmt = $conn->prepare('SELECT user_id, full_name, password, is_verified FROM users WHERE username = ? OR phone = ?');
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                if (!$user['is_verified']) {
                    $alert = '<div class="alert alert-danger">Please verify your phone number.</div>';
                } else {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    header('Location: wallet.php');
                    exit;
                }
            } else {
                $alert = '<div class="alert alert-danger">Invalid username/phone or password.</div>';
            }
        } catch (mysqli_sql_exception $e) {
            $alert = '<div class="alert alert-danger">Server error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
?>

    <div class="container">
        <div class="form-card">
            <h2>Welcome Back</h2>
            <p class="text-center text-muted mb-4">Log in to continue earning rewards.</p>
            <?php echo $alert; ?>
            <form id="login-form" method="POST">
                <input type="text" name="identifier" class="form-control" placeholder="Username or Phone" required>
                <input type="password" name="password" class="form-control" placeholder="Password" required minlength="6">
                <button type="submit" class="btn btn-primary" id="login-submit">
                    Log In
                    <span class="spinner spinner-border spinner-border-sm"></span>
                </button>
            </form>
            <p class="text-center mt-3">
                Don't have an account? <a href="register.php">Sign Up</a>
            </p>
        </div>
    </div>
    <footer>
        <p>© 2025 Looma. All rights reserved.</p>
        <p><a href="index.php">Home</a> | <a href="register.php">Sign Up</a> | <a href="#">Terms</a> | <a href="#">Privacy</a></p>
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

        document.getElementById('login-form').addEventListener('submit', function(event) {
            const identifier = document.querySelector('input[name="identifier"]').value;
            const password = document.querySelector('input[name="password"]').value;
            const button = document.getElementById('login-submit');

            if (!identifier || !password) {
                event.preventDefault();
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger';
                alert.textContent = 'Please fill in all fields.';
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