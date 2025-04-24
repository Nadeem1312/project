<?php
// Start session
session_start();

// Check if user is logged in
$logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Include database connection
require_once 'db_connect.php';

// Function to get available rooms count
function getAvailableRoomsCount($conn, $room_type) {
    // Check if the tables exist first
    $table_check_sql = "SHOW TABLES LIKE '{$room_type}_rooms'";
    $table_result = $conn->query($table_check_sql);
    
    if ($table_result->num_rows == 0) {
        // Table doesn't exist
        echo "Error: Table '{$room_type}_rooms' does not exist.<br>";
        return 0;
    }
    
    // Get total rooms
    $total_sql = "SELECT COUNT(*) as total FROM {$room_type}_rooms WHERE status = 'available'";
    $total_result = $conn->query($total_sql);
    
    if (!$total_result) {
        echo "Error executing total count query: " . $conn->error . "<br>";
        return 0;
    }
    
    $total_row = $total_result->fetch_assoc();
    $total_rooms = $total_row['total'];
    
    return $total_rooms;
}

// Get available counts
$private_available = getAvailableRoomsCount($conn, 'private');
$hotdesk_available = getAvailableRoomsCount($conn, 'hotdesk');
$meeting_available = getAvailableRoomsCount($conn, 'meeting');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation - WorkingSphere 360</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .reservation-container {
            padding: 5rem 0;
            background-color: var(--light-gray);
            min-height: calc(100vh - 200px);
        }
        
        .reservation-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .reservation-header h1 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        
        .reservation-header p {
            color: var(--gray-color);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .spaces-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .space-card {
            background-color: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .space-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .space-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .space-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .space-card:hover .space-image img {
            transform: scale(1.05);
        }
        
        .space-availability {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .space-availability.available {
            background-color: rgba(40, 167, 69, 0.9);
        }
        
        .space-availability.limited {
            background-color: rgba(255, 193, 7, 0.9);
        }
        
        .space-availability.unavailable {
            background-color: rgba(220, 53, 69, 0.9);
        }
        
        .space-details {
            padding: 1.5rem;
        }
        
        .space-title {
            font-size: 1.5rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .space-price {
            font-size: 1.2rem;
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .space-price span {
            font-size: 0.9rem;
            color: var(--gray-color);
            font-weight: 400;
        }
        
        .space-description {
            color: var(--gray-color);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .space-features {
            list-style: none;
            padding: 0;
            margin-bottom: 1.5rem;
        }
        
        .space-features li {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }
        
        .space-features i {
            color: var(--primary-color);
            margin-right: 0.5rem;
            font-size: 0.9rem;
        }
        
        .space-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .book-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .book-btn:hover {
            background-color: var(--secondary-color);
        }
        
        .tour-btn {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .tour-btn i {
            margin-right: 0.5rem;
        }
        
        .tour-btn:hover {
            color: var(--secondary-color);
        }
        
        .login-prompt {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--box-shadow);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .login-prompt h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .login-prompt p {
            color: var(--gray-color);
            margin-bottom: 1.5rem;
        }
        
        .login-prompt .btn {
            margin: 0 0.5rem;
        }
        
        @media (max-width: 768px) {
            .spaces-grid {
                grid-template-columns: 1fr;
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
                    <li><a href="reservation.php" class="active">RESERVATION</a></li>
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

    <!-- Reservation Section -->
    <section class="reservation-container">
        <div class="container">
            <div class="reservation-header">
                <h1>Reserve Your Workspace</h1>
                <p>Choose from our variety of workspace options to find the perfect environment for your needs. Take a virtual tour before booking to ensure it meets your requirements.</p>
            </div>
            
            <?php if($logged_in): ?>
                <div class="spaces-grid">
                    <!-- Private Office Card -->
                    <div class="space-card">
                        <div class="space-image">
                            <img src="images/spaces/private-office-1.jpg" alt="Private Office">
                            <?php if($private_available > 0): ?>
                                <div class="space-availability available">
                                    <?php echo $private_available; ?> Available
                                </div>
                            <?php else: ?>
                                <div class="space-availability unavailable">
                                    Fully Booked
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="space-details">
                            <h2 class="space-title">Private Room</h2>
                            <div class="space-price">70 L.E <span>/ hour</span></div>
                            <p class="space-description">
                                Your personal productivity zone. A fully enclosed space tailored for deep focus and uninterrupted work.
                            </p>
                            <ul class="space-features">
                                <li><i class="fas fa-check-circle"></i> Total Privacy</li>
                                <li><i class="fas fa-check-circle"></i> Ergonomic Comfort</li>
                                <li><i class="fas fa-check-circle"></i> Personal control</li>
                            </ul>
                            <div class="space-actions">
                                <?php if($private_available > 0): ?>
                                    <a href="book.php?type=private" class="book-btn">Book Now</a>
                                <?php else: ?>
                                    <button class="book-btn" disabled style="background-color: #ccc; cursor: not-allowed;">Unavailable</button>
                                <?php endif; ?>
                                <a href="virtual-tour.php?space=private" class="tour-btn"><i class="fas fa-vr-cardboard"></i> 360° Tour</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hot Desk Card -->
                    <div class="space-card">
                        <div class="space-image">
                            <img src="images/spaces/hotdesk-1.jpg" alt="Hot Desk">
                            <?php if($hotdesk_available > 0): ?>
                                <div class="space-availability available">
                                    <?php echo $hotdesk_available; ?> Available
                                </div>
                            <?php elseif($hotdesk_available <= 10 && $hotdesk_available > 0): ?>
                                <div class="space-availability limited">
                                    <?php echo $hotdesk_available; ?> Available
                                </div>
                            <?php else: ?>
                                <div class="space-availability unavailable">
                                    Fully Booked
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="space-details">
                            <h2 class="space-title">Public Desk</h2>
                            <div class="space-price">50 L.E <span>/ hour</span></div>
                            <p class="space-description">
                                Stay connected in the heart of the community. Flexible, open desks made for casual work and collaboration.
                            </p>
                            <ul class="space-features">
                                <li><i class="fas fa-check-circle"></i> Community vibes</li>
                                <li><i class="fas fa-check-circle"></i> Breakout Corner</li>
                                <li><i class="fas fa-check-circle"></i> Quiet Zone</li>
                            </ul>
                            <div class="space-actions">
                                <?php if($hotdesk_available > 0): ?>
                                    <a href="book.php?type=hotdesk" class="book-btn">Book Now</a>
                                <?php else: ?>
                                    <button class="book-btn" disabled style="background-color: #ccc; cursor: not-allowed;">Unavailable</button>
                                <?php endif; ?>
                                <a href="virtual-tour.php?space=hotdesk" class="tour-btn"><i class="fas fa-vr-cardboard"></i> 360° Tour</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Meeting Room Card -->
                    <div class="space-card">
                        <div class="space-image">
                            <img src="images/spaces/meeting-room-1.jpg" alt="Meeting Room">
                            <?php if($meeting_available > 0): ?>
                                <div class="space-availability available">
                                    <?php echo $meeting_available; ?> Available
                                </div>
                            <?php else: ?>
                                <div class="space-availability unavailable">
                                    Fully Booked
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="space-details">
                            <h2 class="space-title">Meeting Room</h2>
                            <div class="space-price">35 L.E <span>/ hour</span></div>
                            <p class="space-description">
                                Collaborate, present and innovate. A small space design for teams and client meetings.
                            </p>
                            <ul class="space-features">
                                <li><i class="fas fa-check-circle"></i> Coffee Corner</li>
                                <li><i class="fas fa-check-circle"></i> Smart Screen</li>
                                <li><i class="fas fa-check-circle"></i> Soundproof</li>
                            </ul>
                            <div class="space-actions">
                                <?php if($meeting_available > 0): ?>
                                    <a href="book.php?type=meeting" class="book-btn">Book Now</a>
                                <?php else: ?>
                                    <button class="book-btn" disabled style="background-color: #ccc; cursor: not-allowed;">Unavailable</button>
                                <?php endif; ?>
                                <a href="virtual-tour.php?space=meeting" class="tour-btn"><i class="fas fa-vr-cardboard"></i> 360° Tour</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="login-prompt">
                    <h2>Login Required</h2>
                    <p>Please login or create an account to book a workspace.</p>
                    <div>
                        <a href="login.php?redirect=reservation.php" class="btn btn-primary">Login</a>
                        <a href="signup.php" class="btn btn-secondary">Sign Up</a>
                    </div>
                </div>
            <?php endif; ?>
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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="reservation.php" class="active">Reservation</a></li>
                        <li><a href="about.php">About Us</a></li>
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
