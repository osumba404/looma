<!-- register.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Looma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="form-card">
            <h2>Create Your Account</h2>
            <p class="text-center text-muted mb-4">Join Looma to start playing and earning!</p>
            <div id="register-alert" class="alert"></div>
            <form id="register-form" onsubmit="registerUser(event)">
                <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
                <input type="text" name="username" class="form-control" placeholder="Username" required>
                <input type="tel" name="phone" class="form-control" placeholder="Phone (e.g., +2547XXXXXXXX)" required pattern="(\+254|0)[17]\d{8}">
                <input type="email" name="email" class="form-control" placeholder="Email (optional)">
                <input type="password" name="password" class="form-control" placeholder="Password" required minlength="6">
                <button type="submit" class="btn btn-primary">
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
    <script src="assets/js/main.js"></script>
</body>
</html>