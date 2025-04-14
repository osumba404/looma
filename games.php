<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | Games</title>
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
            <a href="games.php" class="nav-link active">
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
        
        <!-- Games Section -->
        <section class="games-section py-4">
            <div class="container">
                <h2 class="mb-4">Featured Games</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Memory Match</h5>
                                <p class="card-text">Test your memory skills by matching pairs!</p>
                                <p class="card-text"><small>3 Levels • 10 Mins • 100 Points</small></p>
                                <a href="#" class="btn btn-primary">Play Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Word Scramble</h5>
                                <p class="card-text">Unscramble letters to form words!</p>
                                <p class="card-text"><small>5 Rounds • 8 Mins • 80 Points</small></p>
                                <a href="#" class="btn btn-primary">Play Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Math Blitz</h5>
                                <p class="card-text">Solve math problems against the clock!</p>
                                <p class="card-text"><small>20 Questions • 5 Mins • 90 Points</small></p>
                                <a href="#" class="btn btn-primary">Play Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Game Progress -->
        <section class="game-progress py-4">
            <div class="container">
                <h2 class="mb-4">Your Game Progress</h2>
                <div class="card">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Memory Match: Level 2 Completed - 85/100 points</li>
                        <li class="list-group-item">Word Scramble: Round 4 - 60/80 points</li>
                        <li class="list-group-item">Math Blitz: Best Score - 75/90 points</li>
                        <li class="list-group-item">Daily Challenge Completed - 50 bonus points</li>
                    </ul>
                </div>
            </div>
        </section>
        
        <!-- Achievements -->
        <section class="achievements py-4">
            <div class="container">
                <h2 class="mb-4">Recent Achievements</h2>
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-medal fa-2x mb-2"></i>
                                <h5>First Win</h5>
                                <p>Won your first game</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-star fa-2x mb-2"></i>
                                <h5>Quick Learner</h5>
                                <p>Completed 5 games</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-trophy fa-2x mb-2"></i>
                                <h5>High Scorer</h5>
                                <p>Scored above 80 points</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-crown fa-2x mb-2"></i>
                                <h5>Streak Master</h5>
                                <p>3-day play streak</p>
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
        <a href="games.php" class="mobile-nav-item active">
            <i class="fas fa-gamepad"></i>
            <span>Games</span>
        </a>
        <a href="questions.php" class="mobile-nav-item">
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