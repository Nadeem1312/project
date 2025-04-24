<?php
// Start session
session_start();

// Check if user is logged in
$logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkingSphere 360 - Virtual Workspace Experience</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="container">
            <div class="logo">
            <h1><span>Working</span><span>Sphere</span> <span>360</span></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php" class="active">HOME</a></li>
                    <li><a href="reservation.php">RESERVATION</a></li>
                    <li><a href="about.php">ABOUT US</a></li>
                    <li><a href="contact.php">CONTACT</a></li>
                    <?php if($logged_in): ?>
                        <li><a href="dashboard.php">MY ACCOUNT</a></li>
                        <li><a href="logout.php">LOGOUT</a></li>
                    <?php else: ?>
                        <li><a href="login.php">LOGIN</a></li>
                        <li><a href="signup.php">SIGN UP</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title"><span class="pink-text">Working</span> <span class="white-text">Sphere</span></h1>
                    <div class="hero-details">
                        <div class="detail-item">
                            <p>OPEN DAILY</p>
                            <p>10AM - 12PM</p>
                        </div>
                        <div class="detail-item">
                            <p>YOUR PLACE,</p>
                            <p>Heliopolis</p>
                        </div>
                    </div>
                    <p class="hero-description">
                        Experience our coworking space in immersive 360° virtual reality before booking.
                        Find your perfect workspace with flexible options for freelancers, startups, and remote workers.
                    </p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                    <a href="virtual-tour.html" class="visit-button">VIRTUAL TOUR</a>
                    <?php if(!$logged_in): ?>
                        <a href="signup.php" class="visit-button signup-button">JOIN NOW</a>
                    <?php endif; ?>
                </div>
                <div class="hero-illustration">
                    <img src="11489268.png" alt="Coworking Illustration">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Why Choose WorkingSphere 360</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-vr-cardboard"></i>
                    </div>
                    <h3>Immersive 360° Tours</h3>
                    <p>Explore every corner of our space virtually before making a decision</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Easy Booking</h3>
                    <p>Reserve desks, meeting rooms, and event spaces with a few clicks</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community Networking</h3>
                    <p>Connect with like-minded professionals in our vibrant community</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-coffee"></i>
                    </div>
                    <h3>Premium Amenities</h3>
                    <p>Enjoy high-speed internet, coffee bar, and comfortable workspaces</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to experience our space?</h2>
                <p>Take a virtual tour or reserve your workspace today</p>
                <div class="cta-buttons">
                    <a href="#" class="btn btn-primary">Virtual Tour</a>
                    <?php if($logged_in): ?>
                        <a href="reservation.html" class="btn btn-secondary">Reserve Now</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-secondary">Login to Reserve</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-about">
                    <h3><span class="white-text">Working</span><span class="pink-text">Sphere</span> <span>360</span></h3>
                    <p>Providing immersive virtual workspace experiences to help you find your perfect coworking environment.</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/share/1BSKTmaQVc/"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/working_sphere_360?igsh=NmtkcjIyZDRzNXpm"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="reservation.php">Reservation</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h4>Contact Us</h4>
                    <p><i class="fas fa-map-marker-alt"></i>81 farid semeika st, Heliopolis</p>
                    <p><i class="fas fa-phone"></i> 01091806090</p>
                    <p><i class="fas fa-envelope"></i> info@WorkingSphere.com</p>
                </div>
                <div class="footer-newsletter">
                    <h4>Newsletter</h4>
                    <p>Subscribe to get updates on new features and special offers</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Your email address">
                        <button type="submit" class="btn btn-small">Subscribe</button>
                    </form>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 WorkingSphere 360. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // JavaScript for mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const navLinks = document.querySelector('.nav-links');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    navLinks.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>