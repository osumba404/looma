<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | About</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #343a40;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
        }

        .sidebar-brand h2 {
            font-weight: 700;
        }

        .nav-link {
            color: white;
            margin: 10px 0;
        }

        .nav-link.active {
            background-color: #0d6efd;
            border-radius: 5px;
        }

        .main-content {
            margin-left: 250px;
            transition: margin-left 0.3s;
        }

        .main-content-expanded {
            margin-left: 0;
        }

        .top-navbar {
            background-color: #f8f9fa;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #343a40;
            padding: 10px 0;
            justify-content: space-around;
        }

        .mobile-nav-item {
            color: white;
            text-align: center;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                display: none;
            }

            .mobile-bottom-nav {
                display: flex;
            }

            .main-content {
                margin-left: 0;
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
            <a href="index.php" class="nav-link">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="about.php" class="nav-link active">
                <i class="fas fa-info-circle"></i>
                <span>About us</span>
            </a>
            <a href="contact.php" class="nav-link">
                <i class="fas fa-envelope"></i>
                <span>Contact us</span>
            </a>
            <a href="login.php" class="nav-link">
                <i class="fas fa-sign-in-alt"></i>
                <span>Log in</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <p>&copy; 2025 Looma</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <a href="signup.php"> 
                <div class="user-profile">
                    <i class="fas fa-user-plus"></i>
                    <div>
                        <div class="fw-bold">Sign up</div>
                    </div>
                </div>
            </a>
        </div>

        <!-- About Content -->
        <div class="container py-5">
            <h2 class="mb-4 text-primary fw-bold">About Looma</h2>
            <p class="lead">
                Looma is a unique platform that blends fun and rewards — allowing users to earn while they play. Our mission is to empower users by providing interactive games, quizzes, and referral opportunities that pay real money.
            </p>

            <div class="row mt-5">
                <div class="col-md-6">
                    <h4>🎯 Our Mission</h4>
                    <p>
                        To create an engaging digital environment where entertainment meets opportunity. We strive to make earning fun, accessible, and rewarding for everyone.
                    </p>
                </div>
                <div class="col-md-6">
                    <h4>🌟 Why Choose Looma?</h4>
                    <ul>
                        <li>✅ Simple and fun user interface</li>
                        <li>✅ Real-time earnings through games</li>
                        <li>✅ M-Pesa integration for fast payouts</li>
                        <li>✅ Community-focused with referral bonuses</li>
                        <li>✅ Safe and secure user transactions</li>
                        <li>✅ Regular updates with exciting features</li>
                    </ul>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-md-6">
                    <h4>🚀 Our Vision</h4>
                    <p>
                        To become Africa's leading gamified earning platform by connecting people through play and digital rewards. We envision a future where everyone has equal access to fun and financial growth.
                    </p>
                </div>
                <div class="col-md-6">
                    <h4>👥 Meet The Team</h4>
                    <p>
                        Looma is powered by a passionate team of developers, designers, and digital innovators from Kenya who believe in the future of fun-based earning. We’re committed to continuous improvement and user satisfaction.
                    </p>
                </div>
            </div>

            <div class="mt-5">
                <h4>✨ What Looma Offers</h4>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <i class="fas fa-puzzle-piece fa-2x text-primary mb-3"></i>
                                <h5 class="card-title">Fun & Interactive Games</h5>
                                <p class="card-text">Engage with games that challenge your brain while you earn coins and real money.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <i class="fas fa-bolt fa-2x text-primary mb-3"></i>
                                <h5 class="card-title">Instant Payouts</h5>
                                <p class="card-text">Withdraw your earnings instantly via M-Pesa – safe, secure, and quick!</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <i class="fas fa-users fa-2x text-primary mb-3"></i>
                                <h5 class="card-title">Community & Referrals</h5>
                                <p class="card-text">Invite friends and grow together. Enjoy referral bonuses and special rewards!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <h4>🤝 Join Us Today</h4>
                <p>
                    Whether you're here to enjoy fun games or make extra income, Looma has something for everyone. Join our growing community and turn your free time into earning time.
                </p>
                <a href="signup.php" class="btn btn-primary mt-2">
                    <i class="fas fa-user-plus"></i> Create an Account
                </a>
            </div>
        </div>
    </div>
    
    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="index.php" class="mobile-nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="#" class="mobile-nav-item">
            <i class="fas fa-gamepad"></i>
            <span>Games</span>
        </a>
        <a href="#" class="mobile-nav-item">
            <i class="fas fa-wallet"></i>
            <span>Earnings</span>
        </a>
        <a href="#" class="mobile-nav-item">
            <i class="fas fa-users"></i>
            <span>Refer</span>
        </a>
        <a href="#" class="mobile-nav-item">
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
    </script>
</body>
</html>
