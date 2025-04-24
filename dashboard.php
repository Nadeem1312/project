<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Include database connection
require_once 'db_connect.php';

// Get user ID from session
// Fix for Error #1: Use the correct session variable name
// Change from $_SESSION["id"] to $_SESSION["user_id"] (or whatever your actual session variable is)
$user_id = $_SESSION["user_id"]; // Change this to match your actual session variable name

// Get user information
$sql = "SELECT fullname, email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);

// Check if prepare was successful
// Fix for Error #2: Add error checking for the prepare statement
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows > 0) {
    $stmt->bind_result($fullname, $email);
    $stmt->fetch();
} else {
    // Handle case where user is not found
    die("User not found");
}
$stmt->close();

// Get user bookings
$sql = "SELECT b.id, b.date, b.start_time, b.end_time, b.num_people, b.notes, b.status, 
        CASE 
            WHEN b.room_type = 'private' THEN CONCAT('Private Office #', b.room_id)
            WHEN b.room_type = 'hotdesk' THEN CONCAT('Hot Desk #', b.room_id)
            WHEN b.room_type = 'meeting' THEN CONCAT('Meeting Room #', b.room_id)
        END as room_name
        FROM bookings b
        WHERE b.user_id = ? 
        ORDER BY b.date DESC, b.start_time DESC";

$stmt = $conn->prepare($sql);

// Check if prepare was successful
if ($stmt === false) {
    die("Error preparing bookings statement: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = [];

while($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - WorkingSphere 360</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Dashboard specific styles */
        .dashboard-container {
            padding: 5rem 0;
            background-color: var(--light-gray);
            min-height: calc(100vh - 200px);
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 2rem;
        }
        
        .user-panel {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
        }
        
        .user-info {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1rem;
        }
        
        .user-name {
            font-size: 1.5rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .user-email {
            color: var(--gray-color);
            margin-bottom: 1rem;
        }
        
        .dashboard-menu {
            list-style: none;
            padding: 0;
        }
        
        .dashboard-menu li {
            margin-bottom: 0.5rem;
        }
        
        .dashboard-menu a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: var(--dark-color);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }
        
        .dashboard-menu a:hover, .dashboard-menu a.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .dashboard-menu i {
            margin-right: 0.8rem;
            width: 20px;
            text-align: center;
        }
        
        .dashboard-content {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .dashboard-title {
            color: var(--dark-color);
            margin: 0;
        }
        
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .bookings-table th, .bookings-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .bookings-table th {
            background-color: var(--light-gray);
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .bookings-table tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-confirmed {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #664d03;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--primary-color);
            margin-right: 0.5rem;
            font-size: 1rem;
        }
        
        .action-btn:hover {
            color: var(--secondary-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 0;
            color: var(--gray-color);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--light-gray);
        }
        
        @media (max-width: 992px) {
            .dashboard-grid {
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
                <li><a href="reservation.php">RESERVATION</a></li>
                <li><a href="about.php">ABOUT US</a></li>
                <li><a href="contact.php">CONTACT</a></li>
                <li><a href="dashboard.php" class="active">MY ACCOUNT</a></li>
                <li><a href="logout.php">LOGOUT</a></li>
            </ul>
        </nav>
        <div class="menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</header>

<!-- Dashboard Section -->
<section class="dashboard-container">
    <div class="container">
        <div class="dashboard-grid">
            <!-- User Panel -->
            <div class="user-panel">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 class="user-name"><?php echo htmlspecialchars($fullname); ?></h2>
                    <p class="user-email"><?php echo htmlspecialchars($email); ?></p>
                </div>
                
                <ul class="dashboard-menu">
                    <li><a href="#" class="active"><i class="fas fa-th-large"></i> Dashboard</a></li>
                    <li><a href="reservation.php"><i class="fas fa-calendar-plus"></i> New Reservation</a></li>
                    <li><a href="virtual-tour.php"><i class="fas fa-vr-cardboard"></i> Virtual Tour</a></li>
                    <li><a href="#"><i class="fas fa-user-cog"></i> Account Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h2 class="dashboard-title">My Bookings</h2>
                    <a href="reservation.php" class="btn btn-primary">New Booking</a>
                </div>
                
                <?php if(count($bookings) > 0): ?>
                <div class="table-responsive">
                    <table class="bookings-table">
                        <thead>
                            <tr>
                                <th>Space</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>People</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['date'])); ?></td>
                                <td>
                                    <?php 
                                    echo date('h:i A', strtotime($booking['start_time']));
                                    if(!empty($booking['end_time'])) {
                                        echo ' - ' . date('h:i A', strtotime($booking['end_time']));
                                    }
                                    ?>
                                </td>
                                <td><?php echo $booking['num_people']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($booking['status'] != 'cancelled'): ?>
                                    <form method="post" action="cancel_booking.php" style="display: inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" class="action-btn" title="Cancel Booking">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <button class="action-btn" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Bookings Found</h3>
                    <p>You haven't made any reservations yet. Click the button below to book your first workspace.</p>
                    <a href="reservation.php" class="btn btn-primary">Book Now</a>
                </div>
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
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="reservation.php">Reservation</a></li>
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="contact.html">Contact</a></li>
                    <li><a href="dashboard.php" class="active">My Account</a></li>
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
    });
</script>
</body>
</html>