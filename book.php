<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php?redirect=reservation.php");
    exit;
}

// Include database connection
require_once 'db_connect.php';

// Get space type from URL
$space_type = isset($_GET['type']) ? $_GET['type'] : '';
$space_name = '';
$price = '';
$space_images = [];
$panorama_image = '';

// Validate space type
$valid_types = ['private', 'hotdesk', 'meeting'];
if (!in_array($space_type, $valid_types)) {
    header("Location: reservation.php");
    exit;
}

// Set space details based on type
switch($space_type) {
    case 'private':
        $space_name = 'Private Office';
        $price_label = '/ month';
        $space_images = [
            'private-office-1.jpg',
            'private-office-2.jpg',
            'private-office-3.jpg',
            'private-office-4.jpg'
        ];
        $panorama_image = 'panorama-private-office.jpg';
        break;
    case 'hotdesk':
        $space_name = 'Hot Desk';
        $price_label = '/ month';
        $space_images = [
            'hotdesk-1.jpg',
            'hotdesk-2.jpg',
            'hotdesk-3.jpg',
            'hotdesk-4.jpg'
        ];
        $panorama_image = 'panorama-hotdesk.jpg';
        break;
    case 'meeting':
        $space_name = 'Meeting Room';
        $price_label = '/ hour';
        $space_images = [
            'meeting-room-1.jpg',
            'meeting-room-2.jpg',
            'meeting-room-3.jpg',
            'meeting-room-4.jpg'
        ];
        $panorama_image = 'panorama-meeting-room.jpg';
        break;
}

// Get available rooms
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Check if the table exists
$table_name = "";
$price_field = "";
$room_number_field = "";
$capacity_field = "";

switch($space_type) {
    case 'private':
        $table_name = "private_rooms";
        $price_field = "price";
        $room_number_field = "room_number";
        $capacity_field = "capacity";
        break;
    case 'hotdesk':
        $table_name = "hotdesk_rooms";
        $price_field = "price";
        $room_number_field = "desk_number";
        $capacity_field = "1"; // Default capacity for hot desks
        break;
    case 'meeting':
        $table_name = "meeting_rooms";
        $price_field = "price_per_hour";
        $room_number_field = "room_number";
        $capacity_field = "capacity";
        break;
}

// Check if table exists
$table_check_sql = "SHOW TABLES LIKE '{$table_name}'";
$table_result = $conn->query($table_check_sql);

if ($table_result->num_rows == 0) {
    die("Error: Table '{$table_name}' does not exist. Please run the database setup script.");
}

// Get available rooms
$sql = "SELECT r.id, r.{$room_number_field} as room_number, 
        {$capacity_field} as capacity, r.{$price_field} as price
        FROM {$table_name} r
        WHERE r.status = 'available'
        AND r.id NOT IN (
            SELECT room_id FROM bookings 
            WHERE room_type = ? AND date = ? AND status = 'confirmed'
        )
        ORDER BY r.{$room_number_field}";

$stmt = $conn->prepare($sql);

// Check if prepare was successful
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("ss", $space_type, $date);
$stmt->execute();
$result = $stmt->get_result();

$available_rooms = [];
while ($row = $result->fetch_assoc()) {
    $available_rooms[] = $row;
}
$stmt->close();

// Get the price range
$min_price = 0;
$max_price = 0;

if (!empty($available_rooms)) {
    $min_price = min(array_column($available_rooms, 'price'));
    $max_price = max(array_column($available_rooms, 'price'));
    $price = ($min_price == $max_price) ? 
        "$" . number_format($min_price, 2) . " {$price_label}" : 
        "$" . number_format($min_price, 2) . " - $" . number_format($max_price, 2) . " {$price_label}";
} else {
    // Default prices if no rooms available
    switch($space_type) {
        case 'private':
            $price = "$350.00 / month";
            break;
        case 'hotdesk':
            $price = "$150.00 / month";
            break;
        case 'meeting':
            $price = "$30.00 / hour";
            break;
    }
}

// Initialize variables
$selected_room_id = $selected_date = $start_time = $end_time = $num_people = $notes = "";
$room_err = $date_err = $start_time_err = $end_time_err = $num_people_err = "";
$booking_success = false;
$payment_method = "";

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate room
    if(empty(trim($_POST["room_id"]))) {
        $room_err = "Please select a room.";
    } else {
        $selected_room_id = trim($_POST["room_id"]);
    }

    // Validate date
    if(empty(trim($_POST["date"]))) {
        $date_err = "Please select a date.";
    } else {
        $selected_date = trim($_POST["date"]);
        // Check if date is in the future
        if(strtotime($selected_date) < strtotime(date('Y-m-d'))) {
            $date_err = "Please select a future date.";
        }
    }

    // Validate start time
    if(empty(trim($_POST["start_time"]))) {
        $start_time_err = "Please select a start time.";
    } else {
        $start_time = trim($_POST["start_time"]);
    }

    // Validate end time - now required for all room types
    if(empty(trim($_POST["end_time"]))) {
        $end_time_err = "Please select an end time.";
    } else {
        $end_time = trim($_POST["end_time"]);
        // Check if end time is after start time
        if($end_time <= $start_time) {
            $end_time_err = "End time must be after start time.";
        }
    }

    // Validate number of people
    if(empty(trim($_POST["num_people"]))) {
        $num_people_err = "Please enter the number of people.";
    } else {
        $num_people = trim($_POST["num_people"]);
        // Check if number is valid
        if(!is_numeric($num_people) || $num_people < 1) {
            $num_people_err = "Please enter a valid number of people.";
        }
    }

    // Get payment method
    if(isset($_POST["payment_method"])) {
        $payment_method = $_POST["payment_method"];
    }

    // Get notes
    $notes = trim($_POST["notes"]);

    // Check input errors before inserting in database
    if(empty($room_err) && empty($date_err) && empty($start_time_err) && empty($end_time_err) && empty($num_people_err)) {
        // Check if the room is still available
        $check_sql = "SELECT COUNT(*) as booked FROM bookings 
                     WHERE room_type = ? AND room_id = ? AND date = ? AND status = 'confirmed'";
        
        $check_stmt = $conn->prepare($check_sql);
        
        if ($check_stmt === false) {
            die("Error preparing check statement: " . $conn->error);
        }
        
        $check_stmt->bind_param("sis", $space_type, $selected_room_id, $selected_date);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if($check_row['booked'] > 0) {
            // Room is already booked
            $room_err = "This room is no longer available. Please select another room.";
        } else {
            // If payment method is online, redirect to payment page
            if($payment_method == "online") {
                // Store booking details in session for payment page
                $_SESSION["booking_details"] = [
                    "room_type" => $space_type,
                    "room_id" => $selected_room_id,
                    "date" => $selected_date,
                    "start_time" => $start_time,
                    "end_time" => $end_time,
                    "num_people" => $num_people,
                    "notes" => $notes
                ];
                
                // Redirect to payment page
                header("Location: payment.php");
                exit;
            } else {
                // Insert booking into database with active status
                $insert_sql = "INSERT INTO bookings (user_id, room_type, room_id, date, start_time, end_time, num_people, notes, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
                
                $insert_stmt = $conn->prepare($insert_sql);
                
                if ($insert_stmt === false) {
                    die("Error preparing insert statement: " . $conn->error);
                }
                
                $user_id = $_SESSION["user_id"];
                
                $insert_stmt->bind_param("isisssss", $user_id, $space_type, $selected_room_id, $selected_date, $start_time, $end_time, $num_people, $notes);
                
                if($insert_stmt->execute()) {
                    // Update room availability count
                    $update_sql = "UPDATE {$table_name} SET status = 'booked' WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    
                    if ($update_stmt !== false) {
                        $update_stmt->bind_param("i", $selected_room_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                    
                    $booking_success = true;
                } else {
                    echo "Something went wrong. Please try again later.";
                }
                
                $insert_stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book <?php echo $space_name; ?> - WorkingSphere 360</title>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<!-- Pannellum for 360 view -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
<style>
    .booking-container {
        padding: 5rem 0;
        background-color: var(--light-gray);
        min-height: calc(100vh - 200px);
    }
    
    .booking-form-container {
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .booking-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .booking-header h1 {
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }
    
    .booking-header p {
        color: var(--gray-color);
    }
    
    /* Two-column layout */
    .booking-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
    
    @media (max-width: 992px) {
        .booking-content {
            grid-template-columns: 1fr;
        }
    }
    
    /* Space Preview Section */
    .space-preview {
        margin-bottom: 2rem;
    }
    
    .preview-tabs {
        display: flex;
        border-bottom: 1px solid var(--light-gray);
        margin-bottom: 1.5rem;
    }
    
    .preview-tab {
        padding: 1rem 1.5rem;
        cursor: pointer;
        font-weight: 600;
        color: var(--gray-color);
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }
    
    .preview-tab.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }
    
    .preview-tab:hover {
        color: var(--primary-color);
    }
    
    .preview-content {
        display: none;
    }
    
    .preview-content.active {
        display: block;
    }
    
    /* Main Image */
    .main-image {
        width: 100%;
        height: 300px;
        border-radius: var(--border-radius);
        overflow: hidden;
        margin-bottom: 1rem;
    }
    
    .main-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* Image Gallery */
    .image-gallery {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.5rem;
    }
    
    .gallery-item {
        border-radius: var(--border-radius);
        overflow: hidden;
        cursor: pointer;
        height: 80px;
        position: relative;
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }
    
    .gallery-item.active {
        border-color: var(--primary-color);
    }
    
    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* 360 View */
    #panorama {
        width: 100%;
        height: 400px;
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
    
    /* Booking Details */
    .booking-details {
        background-color: var(--light-gray);
        padding: 1.5rem;
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
    }
    
    .booking-details h2 {
        color: var(--dark-color);
        margin-bottom: 1rem;
        font-size: 1.5rem;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }
    
    .detail-label {
        font-weight: 600;
        color: var(--dark-color);
    }
    
    .detail-value {
        color: var(--gray-color);
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
    
    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid var(--light-gray);
        border-radius: var(--border-radius);
        font-size: 1rem;
    }
    
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-color);
    }
    
    .form-group .error-text {
        color: #dc3545;
        font-size: 0.85rem;
        margin-top: 0.5rem;
    }
    
    .booking-btn {
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
    
    .booking-btn:hover {
        background-color: var(--secondary-color);
    }
    
    .booking-footer {
        text-align: center;
        margin-top: 1.5rem;
    }
    
    .booking-footer a {
        color: var(--primary-color);
        text-decoration: none;
    }
    
    .booking-footer a:hover {
        text-decoration: underline;
    }
    
    .success-message {
        background-color: #d4edda;
        color: #155724;
        padding: 1rem;
        border-radius: var(--border-radius);
        margin-bottom: 1.5rem;
        text-align: center;
    }
    
    .room-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .room-card {
        border: 2px solid var(--light-gray);
        border-radius: var(--border-radius);
        padding: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .room-card:hover {
        border-color: var(--primary-color);
        transform: translateY(-3px);
    }
    
    .room-card.selected {
        border-color: var(--primary-color);
        background-color: rgba(var(--primary-color-rgb), 0.05);
    }
    
    .room-number {
        font-weight: 700;
        font-size: 1.2rem;
        color: var(--dark-color);
        margin-bottom: 0.5rem;
    }
    
    .room-capacity {
        display: flex;
        align-items: center;
        color: var(--gray-color);
        margin-bottom: 0.5rem;
    }
    
    .room-capacity i {
        margin-right: 0.5rem;
    }
    
    .room-price {
        font-weight: 600;
        color: var(--primary-color);
    }
    
    .no-rooms-message {
        background-color: #f8d7da;
        color: #721c24;
        padding: 1rem;
        border-radius: var(--border-radius);
        margin-bottom: 1.5rem;
        text-align: center;
    }
    
    /* Payment method styles */
    .payment-methods {
        margin-bottom: 1.5rem;
    }
    
    .payment-methods h3 {
        margin-bottom: 1rem;
        color: var(--dark-color);
    }
    
    .payment-option {
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
        padding: 0.8rem;
        border: 1px solid var(--light-gray);
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .payment-option:hover {
        background-color: var(--light-gray);
    }
    
    .payment-option.selected {
        border-color: var(--primary-color);
        background-color: rgba(var(--primary-color-rgb), 0.05);
    }
    
    .payment-option input {
        margin-right: 1rem;
    }
    
    .payment-option-label {
        display: flex;
        align-items: center;
        flex: 1;
    }
    
    .payment-option-label i {
        margin-right: 0.8rem;
        font-size: 1.2rem;
        color: var(--primary-color);
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .preview-tabs {
            flex-wrap: wrap;
        }
        
        .preview-tab {
            flex: 1 0 50%;
            text-align: center;
            padding: 0.8rem;
        }
        
        #panorama {
            height: 300px;
        }
        
        .room-cards {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
        
        .image-gallery {
            grid-template-columns: repeat(2, 1fr);
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

<!-- Booking Section -->
<section class="booking-container">
    <div class="container">
        <div class="booking-form-container">
            <div class="booking-header">
                <h1>Book <?php echo $space_name; ?></h1>
                <p>Complete the form below to reserve your workspace</p>
            </div>
            
            <?php if($booking_success): ?>
                <div class="success-message">
                    <h2><i class="fas fa-check-circle"></i> Booking Successful!</h2>
                    <p>Your reservation for <?php echo $space_name; ?> has been confirmed. You can view your booking details in your account dashboard.</p>
                    <div class="booking-footer">
                        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="booking-content">
                    <!-- Left Column: Photos and Preview -->
                    <div class="booking-left-column">
                        <!-- Space Preview Section -->
                        <div class="space-preview">
                            <div class="preview-tabs">
                                <div class="preview-tab active" data-tab="photos">Photos</div>
                                <div class="preview-tab" data-tab="360-view">360° View</div>
                            </div>
                            
                            <div class="preview-content active" id="photos-content">
                                <div class="main-image">
                                    <img id="main-prxeview-image" src="images/spaces/<?php echo $space_images[0]; ?>" alt="<?php echo $space_name; ?>">
                                </div>
                                
                                <div class="image-gallery">
                                    <?php foreach($space_images as $index => $image): ?>
                                    <div class="gallery-item <?php echo $index === 0 ? 'active' : ''; ?>" data-image="<?php echo $image; ?>">
                                        <img src="images/spaces/<?php echo $image; ?>" alt="<?php echo $space_name; ?> Image <?php echo $index + 1; ?>">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="preview-content" id="360-view-content">
                                <div id="panorama"></div>
                                <div class="panorama-instructions">
                                    <p><i class="fas fa-mouse-pointer"></i> Click and drag to look around | <i class="fas fa-search-plus"></i> Scroll to zoom</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="booking-details">
                            <h2>Space Details</h2>
                            <div class="detail-row">
                                <span class="detail-label">Space Type:</span>
                                <span class="detail-value"><?php echo $space_name; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Price Range:</span>
                                <span class="detail-value"><?php echo $price; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Amenities:</span>
                                <span class="detail-value">
                                    <?php if($space_type == 'private'): ?>  
                                        Private, lockable space, 24/7 access, Meeting room credits
                                    <?php elseif($space_type == 'Publicdesk'): ?>
                                        Flexible seating, Business hours access, 5 meeting room hours/month
                                    <?php else: ?>
                                        Video conferencing equipment, Whiteboard & presentation tools
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column: Booking Form -->
                    <div class="booking-right-column">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?type=" . $space_type); ?>" method="post">
                            <?php if(count($available_rooms) > 0): ?>
                                <div class="form-group">
                                    <label>Select a <?php echo $space_name; ?></label>
                                    <div class="room-cards">
                                        <?php foreach($available_rooms as $room): ?>
                                        <div class="room-card" data-room-id="<?php echo $room['id']; ?>">
                                            <div class="room-number"><?php echo htmlspecialchars($room['room_number']); ?></div>
                                            <div class="room-capacity">
                                                <i class="fas fa-user"></i> 
                                                <?php echo $room['capacity']; ?> <?php echo $room['capacity'] > 1 ? 'people' : 'person'; ?>
                                            </div>
                                            <div class="room-price">
                                                $<?php echo number_format($room['price'], 2); ?> <?php echo $price_label; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" id="room_id" name="room_id" value="<?php echo $selected_room_id; ?>">
                                    <?php if(!empty($room_err)): ?>
                                        <span class="error-text"><?php echo $room_err; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="date">Date</label>
                                        <input type="date" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo $selected_date ?: date('Y-m-d'); ?>" required>
                                        <?php if(!empty($date_err)): ?>
                                            <span class="error-text"><?php echo $date_err; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="num_people">Number of People</label>
                                        <input type="number" id="num_people" name="num_people" min="1" value="<?php echo $num_people ?: '1'; ?>" required>
                                        <?php if(!empty($num_people_err)): ?>
                                            <span class="error-text"><?php echo $num_people_err; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="start_time">From Time</label>
                                        <input type="time" id="start_time" name="start_time" value="<?php echo $start_time ?: '09:00'; ?>" required>
                                        <?php if(!empty($start_time_err)): ?>
                                            <span class="error-text"><?php echo $start_time_err; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="end_time">To Time</label>
                                        <input type="time" id="end_time" name="end_time" value="<?php echo $end_time ?: '17:00'; ?>" required>
                                        <?php if(!empty($end_time_err)): ?>
                                            <span class="error-text"><?php echo $end_time_err; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="notes">Special Requests or Notes</label>
                                    <textarea id="notes" name="notes" rows="3"><?php echo $notes; ?></textarea>
                                </div>
                                
                                <div class="payment-methods">
                                    <h3>Payment Method</h3>
                                    <div class="payment-option" data-payment="onsite">
                                        <input type="radio" id="payment_onsite" name="payment_method" value="onsite" checked>
                                        <label for="payment_onsite" class="payment-option-label">
                                            <i class="fas fa-money-bill-wave"></i>
                                            Pay on-site
                                        </label>
                                    </div>
                                    <div class="payment-option" data-payment="online">
                                        <input type="radio" id="payment_online" name="payment_method" value="online">
                                        <label for="payment_online" class="payment-option-label">
                                            <i class="fas fa-credit-card"></i>
                                            Pay online now
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="booking-btn">Confirm Reservation</button>
                            <?php else: ?>
                                <div class="no-rooms-message">
                                    <h3><i class="fas fa-exclamation-circle"></i> No Available Rooms</h3>
                                    <p>Sorry, there are no available <?php echo strtolower($space_name); ?>s for the selected date. Please try another date or space type.</p>
                                </div>
                                <div class="booking-footer">
                                    <a href="reservation.php" class="btn btn-primary">Back to Spaces</a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Image Modal -->
                <div id="imageModal" class="modal">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <img class="modal-content" id="modalImage">
                </div>
                
                <div class="booking-footer">
                    <p>Need to change your selection? <a href="reservation.php">Back to Spaces</a></p>
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
        
        // Tab switching
        const tabs = document.querySelectorAll('.preview-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all content
                document.querySelectorAll('.preview-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Show content for active tab
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-content').classList.add('active');
                
                // Initialize panorama when 360 view tab is clicked
                if (tabId === '360-view') {
                    initPanorama();
                }
            });
        });
        
        // Initialize Pannellum for 360 view
        function initPanorama() {
            if (!window.panoramaInitialized) {
                pannellum.viewer('panorama', {
                    "type": "equirectangular",
                    "panorama": "images/panoramas/<?php echo $panorama_image; ?>",
                    "autoLoad": true,
                    "autoRotate": -2,
                    "compass": true,
                    "preview": "images/panoramas/<?php echo $panorama_image; ?>",
                    "title": "<?php echo $space_name; ?> - 360° View",
                    "author": "WorkingSphere 360"
                });
                window.panoramaInitialized = true;
            }
        }
        
        // Room selection
        const roomCards = document.querySelectorAll('.room-card');
        const roomIdInput = document.getElementById('room_id');
        
        roomCards.forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                roomCards.forEach(c => c.classList.remove('selected'));
                // Add selected class to clicked card
                this.classList.add('selected');
                // Update hidden input value
                roomIdInput.value = this.getAttribute('data-room-id');
            });
        });
        
        // Select first room by default if available
        if (roomCards.length > 0) {
            roomCards[0].click();
        }
        
        // Gallery image selection
        const galleryItems = document.querySelectorAll('.gallery-item');
        const mainImage = document.getElementById('main-preview-image');
        
        galleryItems.forEach(item => {
            item.addEventListener('click', function() {
                // Remove active class from all items
                galleryItems.forEach(i => i.classList.remove('active'));
                // Add active class to clicked item
                this.classList.add('active');
                // Update main image
                mainImage.src = 'images/spaces/' + this.getAttribute('data-image');
            });
        });
        
        // Payment method selection
        const paymentOptions = document.querySelectorAll('.payment-option');
        
        paymentOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Find the radio input inside this option and check it
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Remove selected class from all options
                paymentOptions.forEach(o => o.classList.remove('selected'));
                // Add selected class to clicked option
                this.classList.add('selected');
            });
        });
        
        // Select first payment option by default
        if (paymentOptions.length > 0) {
            paymentOptions[0].classList.add('selected');
        }
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
