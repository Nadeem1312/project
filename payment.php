<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php?redirect=reservation.php");
    exit;
}

// Check if booking details exist in session
if(!isset($_SESSION["booking_details"])) {
    header("Location: reservation.php");
    exit;
}

// Include database connection
require_once 'db_connect.php';

// Get booking details from session
$booking_details = $_SESSION["booking_details"];
$room_type = $booking_details["room_type"];
$room_id = $booking_details["room_id"];
$date = $booking_details["date"];
$start_time = $booking_details["start_time"];
$end_time = $booking_details["end_time"];
$num_people = $booking_details["num_people"];
$notes = $booking_details["notes"];

// Get space name and price
$space_name = '';
$price = 0;
$price_label = '';

// Get room details
$table_name = "";
$price_field = "";
$room_number_field = "";

switch($room_type) {
    case 'private':
        $space_name = 'Private Office';
        $price_label = '/ month';
        $table_name = "private_rooms";
        $price_field = "price";
        $room_number_field = "room_number";
        break;
    case 'hotdesk':
        $space_name = 'Hot Desk';
        $price_label = '/ month';
        $table_name = "hotdesk_rooms";
        $price_field = "price";
        $room_number_field = "desk_number";
        break;
    case 'meeting':
        $space_name = 'Meeting Room';
        $price_label = '/ hour';
        $table_name = "meeting_rooms";
        $price_field = "price_per_hour";
        $room_number_field = "room_number";
        break;
}

// Get room price and number
$sql = "SELECT {$price_field} as price, {$room_number_field} as room_number FROM {$table_name} WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $room = $result->fetch_assoc();
    $price = $room['price'];
    $room_number = $room['room_number'];
} else {
    // Room not found
    header("Location: reservation.php");
    exit;
}
$stmt->close();

// Calculate total price for meeting rooms (based on hours)
if ($room_type == 'meeting') {
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $interval = $start->diff($end);
    $hours = $interval->h + ($interval->i / 60);
    $total_price = $price * $hours;
} else {
    $total_price = $price;
}

// Initialize variables
$card_name = $card_number = $card_expiry_month = $card_expiry_year = $card_cvv = "";
$card_name_err = $card_number_err = $card_expiry_month_err = $card_expiry_year_err = $card_cvv_err = "";
$payment_success = false;

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate card name
    if(empty(trim($_POST["card_name"]))) {
        $card_name_err = "Please enter the name on card.";
    } else {
        $card_name = trim($_POST["card_name"]);
    }
    
    // Validate card number
    if(empty(trim($_POST["card_number"]))) {
        $card_number_err = "Please enter the card number.";
    } else {
        $card_number = trim($_POST["card_number"]);
        // Remove spaces and dashes
        $card_number = str_replace([' ', '-'], '', $card_number);
        // Check if card number is valid (simple check for length and numeric)
        if(!is_numeric($card_number) || strlen($card_number) < 13 || strlen($card_number) > 19) {
            $card_number_err = "Please enter a valid card number.";
        }
    }
    
    // Validate expiry month
    if(empty(trim($_POST["card_expiry_month"]))) {
        $card_expiry_month_err = "Please select the expiry month.";
    } else {
        $card_expiry_month = trim($_POST["card_expiry_month"]);
    }
    
    // Validate expiry year
    if(empty(trim($_POST["card_expiry_year"]))) {
        $card_expiry_year_err = "Please select the expiry year.";
    } else {
        $card_expiry_year = trim($_POST["card_expiry_year"]);
    }
    
    // Validate CVV
    if(empty(trim($_POST["card_cvv"]))) {
        $card_cvv_err = "Please enter the CVV.";
    } else {
        $card_cvv = trim($_POST["card_cvv"]);
        // Check if CVV is valid (3-4 digits)
        if(!is_numeric($card_cvv) || strlen($card_cvv) < 3 || strlen($card_cvv) > 4) {
            $card_cvv_err = "Please enter a valid CVV.";
        }
    }
    
    // Check input errors before processing payment
    if(empty($card_name_err) && empty($card_number_err) && empty($card_expiry_month_err) && empty($card_expiry_year_err) && empty($card_cvv_err)) {
        // In a real application, you would process the payment here
        // For this example, we'll just simulate a successful payment
        
        // Insert booking into database with active status
        $insert_sql = "INSERT INTO bookings (user_id, room_type, room_id, date, start_time, end_time, num_people, notes, status, payment_status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 'paid')";
        
        $insert_stmt = $conn->prepare($insert_sql);
        
        if ($insert_stmt === false) {
            die("Error preparing insert statement: " . $conn->error);
        }
        
        $user_id = $_SESSION["user_id"];
        
        $insert_stmt->bind_param("isisssss", $user_id, $room_type, $room_id, $date, $start_time, $end_time, $num_people, $notes);
        
        if($insert_stmt->execute()) {
            // Update room availability count
            $update_sql = "UPDATE {$table_name} SET status = 'booked' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            
            if ($update_stmt !== false) {
                $update_stmt->bind_param("i", $room_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
            
            // Clear booking details from session
            unset($_SESSION["booking_details"]);
            
            $payment_success = true;
        } else {
            echo "Something went wrong. Please try again later.";
        }
        
        $insert_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment - WorkingSphere 360</title>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
    .payment-container {
        padding: 5rem 0;
        background-color: var(--light-gray);
        min-height: calc(100vh - 200px);
    }
    
    .payment-form-container {
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 2rem;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .payment-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .payment-header h1 {
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }
    
    .payment-header p {
        color: var(--gray-color);
    }
    
    .booking-summary {
        background-color: var(--light-gray);
        padding: 1.5rem;
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
    }
    
    .booking-summary h2 {
        color: var(--dark-color);
        margin-bottom: 1rem;
        font-size: 1.5rem;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }
    
    .summary-label {
        font-weight: 600;
        color: var(--dark-color);
    }
    
    .summary-value {
        color: var(--gray-color);
    }
    
    .total-row {
        display: flex;
        justify-content: space-between;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #ddd;
        font-size: 1.2rem;
    }
    
    .total-label {
        font-weight: 700;
        color: var(--dark-color);
    }
    
    .total-value {
        font-weight: 700;
        color: var(--primary-color);
    }
    
    .payment-form {
        margin-bottom: 2rem;
    }
    
    .payment-form h2 {
        color: var(--dark-color);
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    
    .form-group input, .form-group select {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid var(--light-gray);
        border-radius: var(--border-radius);
        font-size: 1rem;
    }
    
    .form-group input:focus, .form-group select:focus {
        outline: none;
        border-color: var(--primary-color);
    }
    
    .form-group .error-text {
        color: #dc3545;
        font-size: 0.85rem;
        margin-top: 0.5rem;
    }
    
    .card-icons {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .card-icon {
        width: 40px;
        height: 25px;
        background-color: #f8f9fa;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    
    .payment-btn {
        background-color: var(--primary-color);
        color: white;
        border: none;
        padding: 1rem;
        width: 100%;
        border-radius: var(--border-radius);
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }
    
    .payment-btn:hover {
        background-color: var(--secondary-color);
    }
    
    .payment-footer {
        text-align: center;
        margin-top: 1.5rem;
    }
    
    .payment-footer a {
        color: var(--primary-color);
        text-decoration: none;
    }
    
    .payment-footer a:hover {
        text-decoration: underline;
    }
    
    .success-message {
        background-color: #d4edda;
        color: #155724;
        padding: 1.5rem;
        border-radius: var(--border-radius);
        margin-bottom: 1.5rem;
        text-align: center;
    }
    
    .success-message h2 {
        color: #155724;
        margin-bottom: 1rem;
    }
    
    .success-message i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #28a745;
    }
    
    .secure-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 1.5rem;
        color: var(--gray-color);
        font-size: 0.9rem;
    }
    
    .secure-badge i {
        margin-right: 0.5rem;
        color: #28a745;
    }
    
    @media (max-width: 768px) {
        .form-row {
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
                <li><a href="dashboard.php">MY ACCOUNT</a></li>
                <li><a href="logout.php">LOGOUT</a></li>
            </ul>
        </nav>
        <div class="menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</header>

<!-- Payment Section -->
<section class="payment-container">
    <div class="container">
        <div class="payment-form-container">
            <div class="payment-header">
                <h1>Complete Your Payment</h1>
                <p>Secure payment for your workspace reservation</p>
            </div>
            
            <?php if($payment_success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <h2>Payment Successful!</h2>
                    <p>Your payment has been processed successfully and your reservation is confirmed. You can view your booking details in your account dashboard.</p>
                    <div class="payment-footer">
                        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="booking-summary">
                    <h2>Booking Summary</h2>
                    <div class="summary-row">
                        <span class="summary-label">Space:</span>
                        <span class="summary-value"><?php echo $space_name; ?> #<?php echo $room_number; ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Date:</span>
                        <span class="summary-value"><?php echo date('F d, Y', strtotime($date)); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Time:</span>
                        <span class="summary-value">
                            <?php echo date('h:i A', strtotime($start_time)); ?> - 
                            <?php echo date('h:i A', strtotime($end_time)); ?>
                        </span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Number of People:</span>
                        <span class="summary-value"><?php echo $num_people; ?></span>
                    </div>
                    <?php if($room_type == 'meeting'): ?>
                    <div class="summary-row">
                        <span class="summary-label">Rate:</span>
                        <span class="summary-value">$<?php echo number_format($price, 2); ?> per hour</span>
                    </div>
                    <?php else: ?>
                    <div class="summary-row">
                        <span class="summary-label">Rate:</span>
                        <span class="summary-value">$<?php echo number_format($price, 2); ?> <?php echo $price_label; ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="total-row">
                        <span class="total-label">Total:</span>
                        <span class="total-value">$<?php echo number_format($total_price, 2); ?></span>
                    </div>
                </div>
                
                <div class="payment-form">
                    <h2>Payment Details</h2>
                    <div class="card-icons">
                        <div class="card-icon"><i class="fab fa-cc-visa"></i></div>
                        <div class="card-icon"><i class="fab fa-cc-mastercard"></i></div>
                        <div class="card-icon"><i class="fab fa-cc-amex"></i></div>
                        <div class="card-icon"><i class="fab fa-cc-discover"></i></div>
                    </div>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                            <label for="card_name">Name on Card</label>
                            <input type="text" id="card_name" name="card_name" value="<?php echo $card_name; ?>" required>
                            <?php if(!empty($card_name_err)): ?>
                                <span class="error-text"><?php echo $card_name_err; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" placeholder="XXXX XXXX XXXX XXXX" value="<?php echo $card_number; ?>" required>
                            <?php if(!empty($card_number_err)): ?>
                                <span class="error-text"><?php echo $card_number_err; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="card_expiry_month">Expiry Month</label>
                                <select id="card_expiry_month" name="card_expiry_month" required>
                                    <option value="" disabled <?php echo empty($card_expiry_month) ? 'selected' : ''; ?>>Month</option>
                                    <?php for($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo sprintf('%02d', $i); ?>" <?php echo ($card_expiry_month == sprintf('%02d', $i)) ? 'selected' : ''; ?>><?php echo sprintf('%02d', $i); ?></option>
                                    <?php endfor; ?>
                                </select>
                                <?php if(!empty($card_expiry_month_err)): ?>
                                    <span class="error-text"><?php echo $card_expiry_month_err; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="card_expiry_year">Expiry Year</label>
                                <select id="card_expiry_year" name="card_expiry_year" required>
                                    <option value="" disabled <?php echo empty($card_expiry_year) ? 'selected' : ''; ?>>Year</option>
                                    <?php 
                                    $current_year = date('Y');
                                    for($i = $current_year; $i <= $current_year + 10; $i++): 
                                    ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($card_expiry_year == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                                <?php if(!empty($card_expiry_year_err)): ?>
                                    <span class="error-text"><?php echo $card_expiry_year_err; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="card_cvv">CVV</label>
                            <input type="text" id="card_cvv" name="card_cvv" placeholder="XXX" maxlength="4" value="<?php echo $card_cvv; ?>" required>
                            <?php if(!empty($card_cvv_err)): ?>
                                <span class="error-text"><?php echo $card_cvv_err; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="payment-btn">Pay $<?php echo number_format($total_price, 2); ?></button>
                    </form>
                    
                    <div class="secure-badge">
                        <i class="fas fa-lock"></i> Your payment information is secure and encrypted
                    </div>
                </div>
                
                <div class="payment-footer">
                    <p>Need to change your booking? <a href="book.php?type=<?php echo $room_type; ?>">Back to Booking</a></p>
                </div>
            <?php endif; ?>
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
                    <li><a href="reservation.php" class="active">Reservation</a></li>
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="contact.html">Contact</a></li>
                    <li><a href="dashboard.php">My Account</a></li>
                    <li><a href="logout.php">Logout</a></li>
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
        
        // Format card number as user types
        const cardNumberInput = document.getElementById('card_number');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', function(e) {
                // Remove all non-digits
                let value = this.value.replace(/\D/g, '');
                // Add a space after every 4 digits
                value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
                // Update the input value
                this.value = value;
            });
        }
    });
</script>
</body>
</html>
