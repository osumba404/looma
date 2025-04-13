<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | Home</title>
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
            <a href="index.php" class="nav-link active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="games.php" class="nav-link">
                <i class="fas fa-gamepad"></i>
                <span>Games</span>
            </a>
            <a href="wallet.php" class="nav-link">
                <i class="fas fa-chart-line"></i>
                <span>Earnings</span>
            </a>
            <a href="withdraw.php" class="nav-link">
                <i class="fas fa-wallet"></i>
                <span>Withdraw</span>
            </a>
            <a href="referral.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Referrals</span>
            </a>
            <!-- <a href="#" class="nav-link">
                <i class="fas fa-bullhorn"></i>
                <span>Promote</span>
            </a> -->
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
            
            <div class="user-profile">
                <div class="user-avatar">EO</div>
                <div>
                    <div class="fw-bold">Evans Osumba</div>
                   
                </div>
            </div>
        </div>
        
        <!-- Content Container -->
        <div class="content-container">
            <!-- Dashboard Stats -->
            <div class="row animate-fadeIn">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-icon primary">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="card-value">5,280</div>
                        <div class="card-title">Points Earned</div>
                        <a href="#" class="btn btn-sm btn-outline-primary">View History</a>
                    </div>
                </div>
                <div class="col-md-4 delay-1">
                    <div class="dashboard-card">
                        <div class="card-icon success">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="card-value">Ksh1,500.00</div>
                        <div class="card-title">Available Balance</div>
                        <a href="#" class="btn btn-sm btn-outline-success">Withdraw Now</a>
                    </div>
                </div>
                <div class="col-md-4 delay-2">
                    <div class="dashboard-card">
                        <div class="card-icon accent">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-value">24</div>
                        <div class="card-title">Referrals</div>
                        <a href="#" class="btn btn-sm btn-outline-danger">Invite Friends</a>
                    </div>
                </div>
            </div>
            
            <!-- Hero Section -->
            <div class="hero-section animate-fadeIn delay-1">
                <h1 class="hero-title">Earn Money Playing Games!</h1>
                <p class="hero-subtitle">Join thousands of players earning real cash through fun games, surveys, and referrals. Withdraw your earnings instantly via M-Pesa.</p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-hero-primary">Get Started Now</a>
                    <a href="login.php" class="btn btn-hero-outline">Log In</a>
                </div>
            </div>
            
            <!-- Features Section -->
            <div class="features-section">
                <h2 class="section-title animate-fadeIn">Why Choose Looma?</h2>
                <div class="row animate-fadeIn delay-1">
                    <div class="col-md-4 mb-4">
                        <div class="feature-card">
                            <div class="feature-icon games">
                                <i class="fas fa-gamepad"></i>
                            </div>
                            <h3 class="feature-title">Exciting Games</h3>
                            <p class="feature-text">Play trivia, puzzles, and skill games to earn points. New challenges added daily to keep you engaged.</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="feature-card">
                            <div class="feature-icon earn">
                                <i class="fas fa-coins"></i>
                            </div>
                            <h3 class="feature-title">Multiple Earning Ways</h3>
                            <p class="feature-text">Earn from games, referrals, surveys, and watching ads. More ways to earn means more money for you.</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="feature-card">
                            <div class="feature-icon withdraw">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h3 class="feature-title">Instant M-Pesa Withdrawals</h3>
                            <p class="feature-text">Cash out your earnings directly to your M-Pesa account anytime. No waiting, no hassle.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- How It Works -->
            <div class="how-it-works">
                <h2 class="section-title animate-fadeIn">How It Works</h2>
                <div class="steps-container animate-fadeIn delay-1">
                    <div class="step-line"></div>
                    
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3 class="step-title">Sign Up & Verify</h3>
                            <p class="step-text">Create your free account in seconds and verify your phone number to start earning.</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3 class="step-title">Play & Earn</h3>
                            <p class="step-text">Complete games, surveys, and offers to accumulate points that convert to real cash.</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3 class="step-title">Withdraw Instantly</h3>
                            <p class="step-text">Cash out your earnings directly to your M-Pesa account whenever you want.</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h3 class="step-title">Refer & Earn More</h3>
                            <p class="step-text">Invite friends and earn 20% of their earnings for life with our referral program.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Testimonials -->
            <div class="testimonials">
                <h2 class="section-title animate-fadeIn">What Our Users Say</h2>
                <div class="row animate-fadeIn delay-1">
                    <div class="col-md-6 mb-4">
                        <div class="testimonial-card">
                            <p class="testimonial-text">"I was skeptical at first, but Looma has helped me earn over $500 in just 3 months playing games in my free time. The withdrawals are instant and reliable!"</p>
                            <div class="testimonial-author">
                                <div class="author-avatar">
                                    <img src="https://randomuser.me/api/portraits/women/32.jpg" alt="Sarah K." width="50">
                                </div>
                                <div>
                                    <div class="author-name">Sarah K.</div>
                                    <div class="author-title">Premium Member since 2024</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="testimonial-card">
                            <p class="testimonial-text">"The referral program is amazing! I've built a team of 50 people and now earn passive income from their activities. Looma has changed my financial situation."</p>
                            <div class="testimonial-author">
                                <div class="author-avatar">
                                    <img src="https://randomuser.me/api/portraits/men/45.jpg" alt="James M." width="50">
                                </div>
                                <div>
                                    <div class="author-name">James M.</div>
                                    <div class="author-title">Top Affiliate</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Affiliate Programs -->
            <div class="affiliate-programs">
                <h2 class="section-title animate-fadeIn">Featured Programs</h2>
                <div class="row animate-fadeIn delay-1">
                    <div class="col-md-4 mb-4">
                        <div class="program-card">
                            <div class="program-image" style="background-image: url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60');"></div>
                            <div class="program-content">
                                <h3 class="program-title">Trivia Challenge</h3>
                                <span class="program-commission">Earn 50 points per game</span>
                                <p class="program-description">Test your knowledge across various categories and earn points for correct answers.</p>
                                <a href="#" class="btn btn-primary">Play Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="program-card">
                            <div class="program-image" style="background-image: url('https://images.unsplash.com/photo-1542751371-adc38448a05e?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60');"></div>
                            <div class="program-content">
                                <h3 class="program-title">Spin & Win</h3>
                                <span class="program-commission">Up to 200 points per spin</span>
                                <p class="program-description">Spin the wheel daily for a chance to win big points and special bonuses.</p>
                                <a href="#" class="btn btn-primary">Try Your Luck</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="program-card">
                            <div class="program-image" style="background-image: url('https://images.unsplash.com/photo-1434030216411-0b793f4b4173?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60');"></div>
                            <div class="program-content">
                                <h3 class="program-title">Referral Program</h3>
                                <span class="program-commission">20% commission for life</span>
                                <p class="program-description">Earn from your referrals' activities. The more you refer, the more you earn.</p>
                                <a href="#" class="btn btn-primary">Invite Friends</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="#" class="mobile-nav-item active">
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