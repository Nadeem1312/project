<?php
// Start session
session_start();

// Check if user is logged in
$logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Initialize variables
$name = $email = $subject = $message = "";
$name_error = $email_error = $subject_error = $message_error = "";
$form_valid = true;
$form_submitted = false;

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_submitted = true;
    
    // Validate name
    if (empty(trim($_POST["name"]))) {
        $name_error = "Please enter your name";
        $form_valid = false;
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_error = "Please enter your email address";
        $form_valid = false;
    } else {
        $email = trim($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_error = "Please enter a valid email address";
            $form_valid = false;
        }
    }
    
    // Validate subject
    if (empty($_POST["subject"])) {
        $subject_error = "Please select a subject";
        $form_valid = false;
    } else {
        $subject = $_POST["subject"];
    }
    
    // Validate message
    if (empty(trim($_POST["message"]))) {
        $message_error = "Please enter your message";
        $form_valid = false;
    } else {
        $message = trim($_POST["message"]);
    }
    
    // If form is valid, process the submission
    if ($form_valid) {
        // In a real application, you would send an email or save to database here
        // For example:
        /*
        $to = "info@workingsphere.com";
        $email_subject = "New Contact Form Submission: $subject";
        $email_body = "You have received a new message from your website contact form.\n\n";
        $email_body .= "Name: $name\n";
        $email_body .= "Email: $email\n";
        $email_body .= "Subject: $subject\n";
        $email_body .= "Message:\n$message\n";
        
        $headers = "From: $email\n";
        $headers .= "Reply-To: $email";
        
        mail($to, $email_subject, $email_body, $headers);
        */
        
        // Reset form fields after successful submission
        $name = $email = $subject = $message = "";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - WorkingSphere 360</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Contact page specific styles */
        .contact-container {
            padding: 5rem 0;
            background-color: var(--light-gray);
            min-height: calc(100vh - 200px);
        }
        
        .contact-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .contact-header h1 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        
        .contact-header p {
            color: var(--gray-color);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .contact-content {
            display: flex;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 3rem;
        }
        
        .contact-form-container {
            flex: 1;
            padding: 2.5rem;
        }
        
        .contact-image {
            flex: 1;
            background-size: cover;
            background-position: center;
            min-height: 400px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .submit-btn:hover {
            background-color: var(--secondary-color);
        }
        
        .contact-info {
            margin-top: 2rem;
        }
        
        .contact-info h3 {
            color: var(--dark-color);
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .info-item i {
            color: var(--primary-color);
            margin-right: 1rem;
            font-size: 1.2rem;
            margin-top: 0.2rem;
        }
        
        .info-item .info-content {
            flex: 1;
        }
        
        .info-item h4 {
            margin: 0 0 0.3rem 0;
            color: var(--dark-color);
        }
        
        .info-item p {
            margin: 0;
            color: var(--gray-color);
        }
        
        .map-container {
            height: 400px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }
        
        .map-container iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }
        
        .social-links-contact {
            display: flex;
            margin-top: 1.5rem;
        }
        
        .social-links-contact a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: var(--light-gray);
            color: var(--dark-color);
            border-radius: 50%;
            margin-right: 1rem;
            transition: all 0.3s ease;
        }
        
        .social-links-contact a:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-3px);
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        
        @media (max-width: 992px) {
            .contact-content {
                flex-direction: column;
            }
            
            .contact-image {
                min-height: 300px;
                order: -1; /* Move image to top on mobile */
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
                    <li><a href="about.php">ABOUT US</a></li>
                    <li><a href="contact.php" class="active">CONTACT</a></li>
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

    <!-- Contact Section -->
    <section class="contact-container">
        <div class="container">
            <div class="contact-header">
                <h1>Get In Touch</h1>
                <p>Have questions about our workspaces or services? We're here to help. Fill out the form below and our team will get back to you as soon as possible.</p>
            </div>
            
            <div class="contact-content">
                <div class="contact-form-container">
                    <h2>Send Us a Message</h2>
                    
                    <?php if($form_submitted && $form_valid): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> Thank you for your message! We'll get back to you shortly.
                    </div>
                    <?php endif; ?>
                    
                    <form id="contact-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            <?php if(!empty($name_error)): ?>
                                <div class="error-message"><?php echo $name_error; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            <?php if(!empty($email_error)): ?>
                                <div class="error-message"><?php echo $email_error; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <select id="subject" name="subject" required>
                                <option value="" disabled <?php echo empty($subject) ? 'selected' : ''; ?>>Select a subject</option>
                                <option value="General Inquiry" <?php echo ($subject == "General Inquiry") ? 'selected' : ''; ?>>General Inquiry</option>
                                <option value="Reservation Question" <?php echo ($subject == "Reservation Question") ? 'selected' : ''; ?>>Reservation Question</option>
                                <option value="Pricing & Plans" <?php echo ($subject == "Pricing & Plans") ? 'selected' : ''; ?>>Pricing & Plans</option>
                                <option value="Technical Support" <?php echo ($subject == "Technical Support") ? 'selected' : ''; ?>>Technical Support</option>
                                <option value="Feedback" <?php echo ($subject == "Feedback") ? 'selected' : ''; ?>>Feedback</option>
                            </select>
                            <?php if(!empty($subject_error)): ?>
                                <div class="error-message"><?php echo $subject_error; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" required><?php echo htmlspecialchars($message); ?></textarea>
                            <?php if(!empty($message_error)): ?>
                                <div class="error-message"><?php echo $message_error; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="submit-btn">Send Message</button>
                    </form>
                    
                    <div class="contact-info">
                        <h3>Contact Information</h3>
                        
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="info-content">
                                <h4>Address</h4>
                                <p>81 Farid Semeika St, Heliopolis, Cairo, Egypt</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <div class="info-content">
                                <h4>Phone</h4>
                                <p>01091806090</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <div class="info-content">
                                <h4>Email</h4>
                                <p>info@WorkingSphere.com</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <div class="info-content">
                                <h4>Business Hours</h4>
                                <p>Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 4:00 PM<br>Sunday: Closed</p>
                            </div>
                        </div>
                        
                        <div class="social-links-contact">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            
            </div>
            
            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3452.5752695078707!2d31.32372491511566!3d30.08815498186583!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14583e0f1a8b3c33%3A0x6f1b7b24b4dc5f7!2sFarid%20Semeka%2C%20Al%20Matar%2C%20El%20Nozha%2C%20Cairo%20Governorate!5e0!3m2!1sen!2seg!4v1650123456789!5m2!1sen!2seg" allowfullscreen="" loading="lazy"></iframe>
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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="reservation.php">Reservation</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php" class="active">Contact</a></li>
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