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

        .contact-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .contact-info-item i {
            font-size: 1.5rem;
            margin-right: 1rem;
            color: #007bff;
        }

        .contact-form .btn-primary {
            transition: background-color 0.3s ease;
        }

        .contact-form .btn-primary:hover {
            background-color: #0056b3;
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
            <p>© 2025 Looma</p>
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
            <div class="contact-header animate-fadeIn">
                <h1>Contact Us</h1>
                <p>Got questions, ideas, or need support? Reach out, and let’s make your Looma experience even better!</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="contact-info animate-fadeIn">
                        <h3 class="mb-4">Get in Touch</h3>
                        <div class="contact-info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h5>Office Address</h5>
                                <p>Westlands, Nairobi, Kenya</p>
                            </div>
                        </div>
                        <div class="contact-info-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <h5>Phone</h5>
                                <p><a href="tel:+254700000000">+254 700 000 000</a></p>
                            </div>
                        </div>
                        <div class="contact-info-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h5>Email</h5>
                                <p><a href="mailto:support@looma.co.ke">support@looma.co.ke</a></p>
                            </div>
                        </div>
                        <div class="contact-info-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h5>Support Hours</h5>
                                <p>Monday - Friday: 8 AM - 6 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="contact-form animate-fadeIn">
                        <h3 class="mb-4">Send Us a Message</h3>
                        <form id="contact-form">
                            <div class="mb-3">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="name" placeholder="Enter your name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Your Email</label>
                                <input type="email" class="form-control" id="email" placeholder="Enter your email" required>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" placeholder="What’s this about?" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Your Message</label>
                                <textarea class="form-control" id="message" rows="5" placeholder="Tell us more..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="contact-submit">
                                Send Message
                                <span class="spinner spinner-border spinner-border-sm" style="display: none;"></span>
                            </button>
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
            <i class="fas fa-info-circle"></i>
            <span>About</span>
        </a>
        <a href="contact.php" class="mobile-nav-item active">
            <i class="fas fa-envelope"></i>
            <span>Contact</span>
        </a>
        <a href="login.php" class="mobile-nav-item">
            <i class="fas fa-sign-in-alt"></i>
            <span>Log in</span>
        </a>
    </div>

    <script>
        
    function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    // Toggle collapsed state for desktop, active state for mobile
    if (window.innerWidth > 992) {
        sidebar.classList.toggle('sidebar-collapsed');
        mainContent.classList.toggle('main-content-expanded');
    } else {
        sidebar.classList.toggle('active');
    }
}

function handleResize() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (window.innerWidth <= 992) {
        // Mobile: hide sidebar by default, remove collapsed state
        sidebar.classList.remove('active', 'sidebar-collapsed');
        mainContent.classList.remove('main-content-expanded');
    } else {
        // Desktop: show sidebar (non-collapsed by default), ensure active is removed
        sidebar.classList.remove('active');
        sidebar.classList.remove('sidebar-collapsed'); // Ensure sidebar is open by default
        mainContent.classList.remove('main-content-expanded');
    }
}

window.addEventListener('resize', handleResize);
document.addEventListener('DOMContentLoaded', handleResize);

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

        // Form submission handling (client-side feedback)
        document.getElementById('contact-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const button = document.getElementById('contact-submit');
            const spinner = button.querySelector('.spinner');
            button.disabled = true;
            spinner.style.display = 'inline-block';
            setTimeout(() => {
                button.disabled = false;
                spinner.style.display = 'none';
                alert('Message sent! We’ll get back to you soon.');
                document.getElementById('contact-form').reset();
            }, 1000);
        });
    </script>
</body>
</html>