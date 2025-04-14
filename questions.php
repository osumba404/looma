<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | Quizes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
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
                <span>Dashboard</span>
            </a>
            <a href="games.php" class="nav-link">
                <i class="fas fa-gamepad"></i>
                <span>Games</span>
            </a>
            <a href="questions.php" class="nav-link active">
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
            <a href="settings.php" class="nav-link">
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
                <div class="user-avatar">EO</div>
                <div>
                    <div class="fw-bold">Evans Osumba</div>
                </div>
            </div>
        </div>
        
        <!-- Quiz Section -->
        <section class="quiz-section py-4">
            <div class="container">
                <h2 class="mb-4">Available Quizzes</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">General Knowledge</h5>
                                <p class="card-text">Test your knowledge across various topics!</p>
                                <p class="card-text"><small>10 Questions • 5 Mins • 50 Points</small></p>
                                <a href="#" class="btn btn-primary">Start Quiz</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Science Trivia</h5>
                                <p class="card-text">Explore the wonders of science!</p>
                                <p class="card-text"><small>15 Questions • 8 Mins • 75 Points</small></p>
                                <a href="#" class="btn btn-primary">Start Quiz</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">History Challenge</h5>
                                <p class="card-text">How well do you know history?</p>
                                <p class="card-text"><small>12 Questions • 6 Mins • 60 Points</small></p>
                                <a href="#" class="btn btn-primary">Start Quiz</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Recent Activity -->
        <section class="recent-activity py-4">
            <div class="container">
                <h2 class="mb-4">Recent Activity</h2>
                <div class="card">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Completed General Knowledge Quiz - 45/50 points</li>
                        <li class="list-group-item">Earned 25 points from Daily Login Bonus</li>
                        <li class="list-group-item">Referred a friend - 100 bonus points</li>
                        <li class="list-group-item">Completed Science Trivia - 65/75 points</li>
                    </ul>
                </div>
            </div>
        </section>
        
        <!-- Quick Stats -->
        <section class="quick-stats py-4">
            <div class="container">
                <h2 class="mb-4">Your Stats</h2>
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3>1250</h3>
                                <p>Total Points</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3>15</h3>
                                <p>Quizzes Completed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3>3</h3>
                                <p>Active Streaks</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3>#25</h3>
                                <p>Leaderboard Rank</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
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
        <a href="questions.php" class="mobile-nav-item active">
            <i class="fas fa-book"></i>
            <span>Quizzes</span>
        </a>
        <a href="wallet.php" class="mobile-nav-item">
            <i class="fas fa-wallet"></i>
            <span>Earnings</span>
        </a>
        <a href="settings.php" class="mobile-nav-item">
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
        
        // Add animation classes as elements come into view
        const animateElements = document.querySelectorAll('.animate-fadeIn');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fadeIn');
                }
            });
        }, { threshold: 0.1 });
        
        animateElements.forEach(element => {
            observer.observe(element);
        });
    </script>
</body>
</html>