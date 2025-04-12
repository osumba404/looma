<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Looma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="form-card">
            <h2>Welcome Back</h2>
            <p class="text-center text-muted mb-4">Log in to continue earning rewards.</p>
            <div id="login-alert" class="alert"></div>
            <form id="login-form" onsubmit="loginUser(event)">
                <input type="text" name="identifier" class="form-control" placeholder="Username or Phone" required>
                <input type="password" name="password" class="form-control" placeholder="Password" required minlength="6">
                <button type="submit" class="btn btn-primary">
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
    <script src="assets/js/main.js"></script>
</body>
</html>