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
    <title>About Us - WorkingSphere 360</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* About page specific styles */
        .about-hero {
            position: relative;
            height: 60vh;
            background-color: var(--dark-color);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }
        
        .about-hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('images/about-hero.jpg');
            background-size: cover;
            background-position: center;
            opacity: 0.4;
            z-index: 1;
        }
        
        .about-hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            padding: 2rem;
        }
        
        .about-hero-title {
            font-size: 3rem;
            margin-bottom: 1.5rem;
        }
        
        .about-hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .about-section {
            padding: 5rem 0;
        }
        
        .about-section.bg-light {
            background-color: var(--light-gray);
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .section-title p {
            color: var(--gray-color);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .about-story {
            display: flex;
            align-items: center;
            gap: 3rem;
            margin-bottom: 5rem;
        }
        
        .about-story-image {
            flex: 1;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }
        
        .about-story-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .about-story-content {
            flex: 1;
        }
        
        .about-story-content h3 {
            font-size: 1.8rem;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
        }
        
        .about-story-content p {
            color: var(--gray-color);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .mission-card, .vision-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            text-align: center;
        }
        
        .mission-card h3, .vision-card h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .mission-card p, .vision-card p {
            color: var(--gray-color);
            line-height: 1.6;
        }
        
        .icon-box {
            width: 80px;
            height: 80px;
            background-color: var(--light-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .icon-box i {
            font-size: 2rem;
            color: var(--primary-color);
        }
        
        .values-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .value-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .value-card:hover {
            transform: translateY(-10px);
        }
        
        .value-card h3 {
            color: var(--dark-color);
            margin: 1rem 0;
            font-size: 1.3rem;
        }
        
        .value-card p {
            color: var(--gray-color);
            line-height: 1.6;
        }
        
        .team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    justify-content: center;
    max-width: 1200px;
    margin: 0 auto;
}

@media (max-width: 992px) {
    .team-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 300px));
    }
}

@media (max-width: 768px) {
    .team-grid {
        grid-template-columns: minmax(250px, 350px);
    }
}
        
        .team-member {
            background-color: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
        }
        
        .team-member:hover {
            transform: translateY(-10px);
        }
        
        .team-member-image {
            height: 350px;
            overflow: hidden;
        }
        
        .team-member-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .team-member:hover .team-member-image img {
            transform: scale(1.1);
        }
        
        .team-member-info {
            padding: 1.5rem;
            text-align: center;
        }
        
        .team-member-info h3 {
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }
        
        .team-member-info p {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .team-social {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .team-social a {
            width: 35px;
            height: 35px;
            background-color: var(--light-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark-color);
            transition: all 0.3s ease;
        }
        
        .team-social a:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .testimonials {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .testimonial {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }
        
        .testimonial-content {
            position: relative;
            padding: 1.5rem 0;
        }
        
        .testimonial-content:before {
            content: '\201C';
            font-size: 4rem;
            color: var(--primary-color);
            opacity: 0.3;
            position: absolute;
            top: -1rem;
            left: -1rem;
        }
        
        .testimonial-content p {
            color: var(--gray-color);
            line-height: 1.6;
            font-style: italic;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
            margin-top: 1.5rem;
        }
        
        .testimonial-author-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 1rem;
        }
        
        .testimonial-author-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .testimonial-author-info h4 {
            color: var(--dark-color);
            margin-bottom: 0.3rem;
        }
        
        .testimonial-author-info p {
            color: var(--gray-color);
            font-size: 0.9rem;
        }
        
        .stats-section {
            background-color: var(--primary-color);
            color: white;
            padding: 4rem 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            text-align: center;
        }
        
        .stat-item h3 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-item p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .cta-section {
            text-align: center;
            padding: 5rem 0;
        }
        
        .cta-content {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .cta-content h2 {
            font-size: 2.5rem;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
        }
        
        .cta-content p {
            color: var(--gray-color);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        @media (max-width: 992px) {
            .about-story {
                flex-direction: column;
            }
            
            .mission-vision {
                grid-template-columns: 1fr;
            }
            
            .values-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .team-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                row-gap: 3rem;
            }
        }
        
        @media (max-width: 768px) {
            .values-grid {
                grid-template-columns: 1fr;
            }
            
            .team-grid {
                grid-template-columns: 1fr;
            }
            
            .cta-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="container">
            <div class="logo">
                <h1><span class="white-text">Working</span><span class="pink-text">Sphere</span> <span>360</span></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">HOME</a></li>
                    <li><a href="reservation.php">RESERVATION</a></li>
                    <li><a href="about.php" class="active">ABOUT US</a></li>
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

    <!-- About Hero Section -->
    <section class="about-hero">
        <div class="about-hero-bg"></div>
        <div class="about-hero-content">
            <h1 class="about-hero-title">About WorkingSphere 360</h1>
            <p class="about-hero-subtitle">Redefining the way professionals work with immersive coworking spaces designed for productivity and collaboration.</p>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="about-section">
        <div class="container">
            <div class="section-title">
                <h2>Our Story</h2>
                <p>How we evolved from a simple idea to a revolutionary workspace solution</p>
            </div>
            
            <div class="about-story">
                <div class="about-story-image">
                    <img src="10826209.png" alt="WorkingSphere 360 Story">
                </div>
                <div class="about-story-content">
                    <h3>From Vision to Reality</h3>
                    <p>WorkingSphere 360 was founded in 2018 with a simple yet powerful vision: to create workspaces that inspire creativity, foster collaboration, and enhance productivity. Our founders, a team of entrepreneurs and design specialists, recognized the changing landscape of work and the growing need for flexible, innovative workspace solutions.</p>
                    <p>What began as a single location in Cairo has now grown into multiple state-of-the-art coworking spaces across Egypt. Our journey has been driven by a commitment to excellence and a deep understanding of what professionals need to thrive in today's dynamic work environment.</p>
                    <p>Today, WorkingSphere 360 stands as a testament to our dedication to creating not just workspaces, but communities where ideas flourish and businesses grow. Our immersive 360° virtual tours represent our innovative approach to helping professionals find their perfect workspace.</p>
                </div>
            </div>
            
            <div class="mission-vision">
                <div class="mission-card">
                    <div class="icon-box">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Our Mission</h3>
                    <p>To provide innovative, flexible workspace solutions that empower professionals and businesses to achieve their full potential through thoughtfully designed environments and supportive communities.</p>
                </div>
                
                <div class="vision-card">
                    <div class="icon-box">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Our Vision</h3>
                    <p>To be the leading provider of immersive workspace experiences, revolutionizing how people discover, experience, and utilize professional environments in the digital age.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values Section -->
    <section class="about-section bg-light">
        <div class="container">
            <div class="section-title">
                <h2>Our Core Values</h2>
                <p>The principles that guide everything we do</p>
            </div>
            
            <div class="values-grid">
                <div class="value-card">
                    <div class="icon-box">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3>Innovation</h3>
                    <p>We constantly seek new ways to improve the workspace experience, embracing technology and creative solutions to stay ahead of evolving needs.</p>
                </div>
                
                <div class="value-card">
                    <div class="icon-box">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community</h3>
                    <p>We believe in the power of connection and collaboration, fostering environments where professionals can network, share ideas, and grow together.</p>
                </div>
                
                <div class="value-card">
                    <div class="icon-box">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Excellence</h3>
                    <p>We are committed to delivering exceptional quality in every aspect of our service, from the design of our spaces to our customer support.</p>
                </div>
                
                <div class="value-card">
                    <div class="icon-box">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3>Sustainability</h3>
                    <p>We prioritize environmentally responsible practices in our operations and workspace designs, minimizing our ecological footprint.</p>
                </div>
                
                <div class="value-card">
                    <div class="icon-box">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Integrity</h3>
                    <p>We conduct our business with honesty, transparency, and ethical standards that build trust with our clients and partners.</p>
                </div>
                
                <div class="value-card">
                    <div class="icon-box">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Passion</h3>
                    <p>We are driven by a genuine enthusiasm for creating spaces that inspire and enable people to do their best work.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Team Section -->
    <section class="about-section">
        <div class="container">
            <div class="section-title">
                <h2>Meet Our Team</h2>
                <p>The talented individuals behind WorkingSphere 360</p>
            </div>
            
            <div class="team-grid">
                <div class="team-member">
                    <div class="team-member-image">
                        <img src="nadeem.jpg" alt="Team Member">
                    </div>
                    <div class="team-member-info">
                        <h3>Nadim kamel</h3>
                        <p>Founder & CEO</p>
                        <div class="team-social">
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="team-member-image">
                        <img src="rawan.jpg" alt="Team Member">
                    </div>
                    <div class="team-member-info">
                        <h3>Rawan Ashraf</h3>
                        <p>Design Lead</p>
                        <div class="team-social">
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="team-member-image">
                        <img src="yassin.jpg" alt="Team Member">
                    </div>
                    <div class="team-member-info">
                        <h3>Yassin Ashraf</h3>
                        <p>Customer Experience</p>
                        <div class="team-social">
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="about-section bg-light">
        <div class="container">
            <div class="section-title">
                <h2>What Our Members Say</h2>
                <p>Hear from the professionals who call WorkingSphere 360 home</p>
            </div>
            
            <div class="testimonials">
                <div class="testimonial">
                    <div class="testimonial-content">
                        <p>"WorkingSphere 360 has completely transformed how I work. The virtual tour feature allowed me to explore the space before committing, and the actual experience has exceeded my expectations. The community here is incredible, and the amenities are top-notch."</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="testimonial-author-image">
                            <img src="images/testimonials/testimonial-1.jpg" alt="Testimonial Author">
                        </div>
                        <div class="testimonial-author-info">
                            <h4>Sara Khalid</h4>
                            <p>Freelance Designer</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial">
                    <div class="testimonial-content">
                        <p>"As a startup founder, finding the right workspace was crucial for our team's productivity and growth. WorkingSphere 360 provided not just a beautiful office space, but a supportive ecosystem that has helped us thrive. The booking process was seamless, and the staff is always ready to assist."</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="testimonial-author-image">
                            <img src="images/testimonials/testimonial-2.jpg" alt="Testimonial Author">
                        </div>
                        <div class="testimonial-author-info">
                            <h4>Karim Mostafa</h4>
                            <p>CEO, TechStart</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial">
                    <div class="testimonial-content">
                        <p>"The flexibility WorkingSphere 360 offers is unmatched. I can book a private office when I need focus time or use a hot desk when I'm just dropping in. The 360° virtual tours made it easy to see exactly what I was getting before I booked. It's become an essential part of my work life."</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="testimonial-author-image">
                            <img src="images/testimonials/testimonial-3.jpg" alt="Testimonial Author">
                        </div>
                        <div class="testimonial-author-info">
                            <h4>Amira Salah</h4>
                            <p>Marketing Consultant</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>5+</h3>
                    <p>Years of Experience</p>
                </div>
                
                <div class="stat-item">
                    <h3>3</h3>
                    <p>Locations</p>
                </div>
                
                <div class="stat-item">
                    <h3>500+</h3>
                    <p>Happy Members</p>
                </div>
                
                <div class="stat-item">
                    <h3>98%</h3>
                    <p>Satisfaction Rate</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Experience WorkingSphere 360?</h2>
                <p>Discover the perfect workspace for your needs with our immersive virtual tours and flexible booking options.</p>
                <div class="cta-buttons">
                    <a href="virtual-tour.php" class="btn btn-primary">Take a Virtual Tour</a>
                    <a href="reservation.php" class="btn btn-secondary">Book a Space</a>
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
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="reservation.php">Reservation</a></li>
                        <li><a href="about.php" class="active">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <?php if($logged_in): ?>
                            <li><a href="dashboard.php">My Account</a></li>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="signup.php">Sign Up</a></li>
                        <?php endif; ?>
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
            const navLinks = document.querySelector('nav ul');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    navLinks.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>