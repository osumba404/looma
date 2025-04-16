<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | Contact</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .contact-section {
            padding: 2rem;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .contact-info {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
        }

        .contact-form {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        .contact-form input, .contact-form textarea {
            margin-bottom: 1rem;
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
            <a href="about.php" class="nav-link">
                <i class="fas fa-info-circle"></i>
                <span>About us</span>
            </a>
            <a href="contact.php" class="nav-link active">
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
                <div class="user-profile">
                   
                    <div>
                        <div class="fw-bold">LOOMA</div>
                    </div>
                </div>
        </div>
        <!-- Contact Section -->
        <div class="container contact-section">
            <div class="contact-header">
                <h1>Contact Us</h1>
                <p>Have any questions or suggestions? We'd love to hear from you.</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="contact-info">
                        <h5><i class="fas fa-map-marker-alt"></i> Office Address</h5>
                        <p>Nairobi, Kenya</p>

                        <h5><i class="fas fa-phone"></i> Phone</h5>
                        <p>+254 700 000 000</p>

                        <h5><i class="fas fa-envelope"></i> Email</h5>
                        <p>support@looma.co.ke</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="contact-form">
                        <h5>Send a Message</h5>
                        <form>
                            <input type="text" class="form-control" placeholder="Your Name" required>
                            <input type="email" class="form-control" placeholder="Your Email" required>
                            <textarea class="form-control" rows="4" placeholder="Your Message" required></textarea>
                            <button type="submit" class="btn btn-primary w-100">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Mobile Bottom Navigation -->
<div class="mobile-bottom-nav">
    <a href="index.php" class="mobile-nav-item">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    <a href="about.php" class="mobile-nav-item">
        <i class="fas fa-info-circle"></i> <!-- Updated to info icon -->
        <span>About</span>
    </a>
    <a href="contact.php" class="mobile-nav-item active">
        <i class="fas fa-envelope"></i> <!-- Updated to contact/envelope icon -->
        <span>Contact</span>
    </a>
    <a href="login.php" class="mobile-nav-item">
        <i class="fas fa-sign-in-alt"></i> <!-- Updated to login icon -->
        <span>Log in</span>
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
