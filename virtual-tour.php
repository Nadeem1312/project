<?php
// Start session
session_start();

// Check if user is logged in
$logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Get space type from URL if available
$space_type = isset($_GET['space']) ? $_GET['space'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Tour - WorkingSphere 360</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Pannellum for 360 view -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    <style>
        .tour-container {
            padding: 5rem 0;
            background-color: var(--light-gray);
            min-height: calc(100vh - 200px);
        }
        
        .tour-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .tour-header h1 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        
        .tour-header p {
            color: var(--gray-color);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .tour-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .tour-tab {
            padding: 1rem 2rem;
            background-color: white;
            border-radius: var(--border-radius);
            margin: 0 0.5rem 1rem;
            cursor: pointer;
            font-weight: 600;
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
        }
        
        .tour-tab.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .tour-tab:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .tour-content {
            display: none;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .tour-content.active {
            display: block;
        }
        
        .panorama-container {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .panorama {
            width: 100%;
            height: 500px;
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        .panorama-instructions {
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 1rem;
            border-radius: var(--border-radius);
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 100;
            text-align: center;
            max-width: 80%;
            font-size: 0.9rem;
            pointer-events: none;
            opacity: 0.9;
        }
        
        .space-info {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
        }
        
        .space-details {
            flex: 1;
            min-width: 300px;
        }
        
        .space-details h2 {
            color: var(--dark-color);
            margin-bottom: 1rem;
        }
        
        .space-price {
            font-size: 1.5rem;
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .space-price span {
            font-size: 1rem;
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
            margin-top: 2rem;
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
        
        .space-gallery {
            flex: 1;
            min-width: 300px;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .gallery-item {
            border-radius: var(--border-radius);
            overflow: hidden;
            cursor: pointer;
            height: 120px;
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .gallery-item:hover img {
            transform: scale(1.05);
        }
        
        /* Modal for full-size images */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
        }
        
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            z-index: 1001;
        }
        
        .close:hover,
        .close:focus {
            color: var(--primary-color);
            text-decoration: none;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .panorama {
                height: 350px;
            }
            
            .tour-tab {
                padding: 0.8rem 1.2rem;
                margin: 0 0.3rem 0.8rem;
                font-size: 0.9rem;
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
                    <li><a href="index.html">HOME</a></li>
                    <li><a href="reservation.php">RESERVATION</a></li>
                    <li><a href="about.html">ABOUT US</a></li>
                    <li><a href="contact.html">CONTACT</a></li>
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

    <!-- Virtual Tour Section -->
    <section class="tour-container">
        <div class="container">
            <div class="tour-header">
                <h1>Virtual Tour</h1>
                <p>Experience our workspace in immersive 360° before making your reservation. Explore each space and find the perfect fit for your needs.</p>
            </div>
            
            <div class="tour-tabs">
                <div class="tour-tab <?php echo ($space_type == 'private' || $space_type == '') ? 'active' : ''; ?>" data-tab="private">Private Office</div>
                <div class="tour-tab <?php echo ($space_type == 'hotdesk') ? 'active' : ''; ?>" data-tab="hotdesk">Hot Desk</div>
                <div class="tour-tab <?php echo ($space_type == 'meeting') ? 'active' : ''; ?>" data-tab="meeting">Meeting Room</div>
            </div>
            
            <!-- Private Office Tour -->
            <div class="tour-content <?php echo ($space_type == 'private' || $space_type == '') ? 'active' : ''; ?>" id="private-content">
                <div class="panorama-container">
                    <div id="panorama-private" class="panorama"></div>
                    <div class="panorama-instructions">
                        <p><i class="fas fa-mouse-pointer"></i> Click and drag to look around | <i class="fas fa-search-plus"></i> Scroll to zoom</p>
                    </div>
                </div>
                
                <div class="space-info">
                    <div class="space-details">
                        <h2>Private Office</h2>
                        <div class="space-price">$350 <span>/ month</span></div>
                        
                        <p class="space-description">
                            Our private offices provide the perfect balance of privacy and community. Ideal for teams of 1-4 people who need a dedicated, secure workspace with all the amenities of our coworking community. Each office comes fully furnished with ergonomic chairs, desks, and storage solutions.
                        </p>
                        
                        <ul class="space-features">
                            <li><i class="fas fa-check-circle"></i> Private, lockable space</li>
                            <li><i class="fas fa-check-circle"></i> 24/7 access</li>
                            <li><i class="fas fa-check-circle"></i> High-speed internet</li>
                            <li><i class="fas fa-check-circle"></i> Dedicated desk & ergonomic chair</li>
                            <li><i class="fas fa-check-circle"></i> Meeting room credits included</li>
                            <li><i class="fas fa-check-circle"></i> Mail handling & business address</li>
                            <li><i class="fas fa-check-circle"></i> Access to printer & scanner</li>
                            <li><i class="fas fa-check-circle"></i> Free coffee & refreshments</li>
                        </ul>
                        
                        <div class="space-actions">
                            <a href="book.php?type=private" class="book-btn">Book This Space</a>
                        </div>
                    </div>
                    
                    <div class="space-gallery">
                        <h3>Photo Gallery</h3>
                        <div class="gallery-grid">
                            <div class="gallery-item" onclick="openModal('private-office-1.jpg')">
                                <img src="images/spaces/private-office-1.jpg" alt="Private Office Image 1">
                            </div>
                            <div class="gallery-item" onclick="openModal('private-office-2.jpg')">
                                <img src="images/spaces/private-office-2.jpg" alt="Private Office Image 2">
                            </div>
                            <div class="gallery-item" onclick="openModal('private-office-3.jpg')">
                                <img src="images/spaces/private-office-3.jpg" alt="Private Office Image 3">
                            </div>
                            <div class="gallery-item" onclick="openModal('private-office-4.jpg')">
                                <img src="images/spaces/private-office-4.jpg" alt="Private Office Image 4">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hot Desk Tour -->
            <div class="tour-content <?php echo ($space_type == 'hotdesk') ? 'active' : ''; ?>" id="hotdesk-content">
                <div class="panorama-container">
                    <div id="panorama-hotdesk" class="panorama"></div>
                    <div class="panorama-instructions">
                        <p><i class="fas fa-mouse-pointer"></i> Click and drag to look around | <i class="fas fa-search-plus"></i> Scroll to zoom</p>
                    </div>
                </div>
                
                <div class="space-info">
                    <div class="space-details">
                        <h2>Hot Desk</h2>
                        <div class="space-price">$150 <span>/ month</span></div>
                        
                        <p class="space-description">
                            Our hot desks offer the perfect solution for freelancers and remote workers who need a professional environment without the commitment of a dedicated space. Work from any available desk in our open coworking area. Enjoy a vibrant community atmosphere and all the amenities you need to be productive.
                        </p>
                        
                        <ul class="space-features">
                            <li><i class="fas fa-check-circle"></i> Flexible seating</li>
                            <li><i class="fas fa-check-circle"></i> Business hours access</li>
                            <li><i class="fas fa-check-circle"></i> High-speed internet</li>
                            <li><i class="fas fa-check-circle"></i> Access to common areas</li>
                            <li><i class="fas fa-check-circle"></i> 5 meeting room hours/month</li>
                            <li><i class="fas fa-check-circle"></i> Free coffee & refreshments</li>
                            <li><i class="fas fa-check-circle"></i> Access to printer & scanner</li>
                            <li><i class="fas fa-check-circle"></i> Community events</li>
                        </ul>
                        
                        <div class="space-actions">
                            <a href="book.php?type=hotdesk" class="book-btn">Book This Space</a>
                        </div>
                    </div>
                    
                    <div class="space-gallery">
                        <h3>Photo Gallery</h3>
                        <div class="gallery-grid">
                            <div class="gallery-item" onclick="openModal('hotdesk-1.jpg')">
                                <img src="images/spaces/hotdesk-1.jpg" alt="Hot Desk Image 1">
                            </div>
                            <div class="gallery-item" onclick="openModal('hotdesk-2.jpg')">
                                <img src="images/spaces/hotdesk-2.jpg" alt="Hot Desk Image 2">
                            </div>
                            <div class="gallery-item" onclick="openModal('hotdesk-3.jpg')">
                                <img src="images/spaces/hotdesk-3.jpg" alt="Hot Desk Image 3">
                            </div>
                            <div class="gallery-item" onclick="openModal('hotdesk-4.jpg')">
                                <img src="images/spaces/hotdesk-4.jpg" alt="Hot Desk Image 4">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Meeting Room Tour -->
            <div class="tour-content <?php echo ($space_type == 'meeting') ? 'active' : ''; ?>" id="meeting-content">
                <div class="panorama-container">
                    <div id="panorama-meeting" class="panorama"></div>
                    <div class="panorama-instructions">
                        <p><i class="fas fa-mouse-pointer"></i> Click and drag to look around | <i class="fas fa-search-plus"></i> Scroll to zoom</p>
                    </div>
                </div>
                
                <div class="space-info">
                    <div class="space-details">
                        <h2>Meeting Room</h2>
                        <div class="space-price">$30 <span>/ hour</span></div>
                        
                        <p class="space-description">
                            Our meeting rooms provide the perfect professional environment for client meetings, team collaborations, or presentations. Fully equipped with modern technology and available in various sizes to accommodate your needs. Book by the hour and only pay for what you need.
                        </p>
                        
                        <ul class="space-features">
                            <li><i class="fas fa-check-circle"></i> Professional setting</li>
                            <li><i class="fas fa-check-circle"></i> Video conferencing equipment</li>
                            <li><i class="fas fa-check-circle"></i> Whiteboard & presentation tools</li>
                            <li><i class="fas fa-check-circle"></i> Coffee & refreshments</li>
                            <li><i class="fas fa-check-circle"></i> Various room sizes (4-12 people)</li>
                            <li><i class="fas fa-check-circle"></i> High-speed internet</li>
                            <li><i class="fas fa-check-circle"></i> LCD display</li>
                            <li><i class="fas fa-check-circle"></i> Adjustable lighting</li>
                        </ul>
                        
                        <div class="space-actions">
                            <a href="book.php?type=meeting" class="book-btn">Book This Space</a>
                        </div>
                    </div>
                    
                    <div class="space-gallery">
                        <h3>Photo Gallery</h3>
                        <div class="gallery-grid">
                            <div class="gallery-item" onclick="openModal('meeting-room-1.jpg')">
                                <img src="images/spaces/meeting-room-1.jpg" alt="Meeting Room Image 1">
                            </div>
                            <div class="gallery-item" onclick="openModal('meeting-room-2.jpg')">
                                <img src="images/spaces/meeting-room-2.jpg" alt="Meeting Room Image 2">
                            </div>
                            <div class="gallery-item" onclick="openModal('meeting-room-3.jpg')">
                                <img src="images/spaces/meeting-room-3.jpg" alt="Meeting Room Image 3">
                            </div>
                            <div class="gallery-item" onclick="openModal('meeting-room-4.jpg')">
                                <img src="images/spaces/meeting-room-4.jpg" alt="Meeting Room Image 4">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Image Modal -->
            <div id="imageModal" class="modal">
                <span class="close" onclick="closeModal()">&times;</span>
                <img class="modal-content" id="modalImage">
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
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="contact.html">Contact</a></li>
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
            
            // Tab switching
            const tabs = document.querySelectorAll('.tour-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('active'));
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Hide all content
                    document.querySelectorAll('.tour-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Show content for active tab
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId + '-content').classList.add('active');
                    
                    // Initialize panorama for the active tab
                    initPanorama(tabId);
                });
            });
            
            // Initialize Pannellum for 360 views
            function initPanorama(spaceType) {
                if (!spaceType) {
                    spaceType = '<?php echo ($space_type) ? $space_type : "private"; ?>';
                }
                
                if (!window['panoramaInitialized_' + spaceType]) {
                    pannellum.viewer('panorama-' + spaceType, {
                        "type": "equirectangular",
                        "panorama": "images/panoramas/panorama-" + spaceType + ".jpg",
                        "autoLoad": true,
                        "autoRotate": -2,
                        "compass": true,
                        "preview": "images/panoramas/panorama-" + spaceType + ".jpg",
                        "title": spaceType.charAt(0).toUpperCase() + spaceType.slice(1) + " - 360° View",
                        "author": "WorkingSphere 360"
                    });
                    window['panoramaInitialized_' + spaceType] = true;
                }
            }
            
            // Initialize the default panorama
            initPanorama();
        });
        
        // Modal functions for image gallery
        function openModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = "block";
            modalImg.src = "images/spaces/" + imageSrc;
        }
        
        function closeModal() {
            document.getElementById('imageModal').style.display = "none";
        }
        
        // Close modal when clicking outside the image
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>