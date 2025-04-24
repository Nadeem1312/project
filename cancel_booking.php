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

// Check if booking_id is provided
if(!isset($_POST["booking_id"]) || empty($_POST["booking_id"])) {
    header("Location: dashboard.php");
    exit;
}

$booking_id = $_POST["booking_id"];
$user_id = $_SESSION["user_id"]; // Use your actual session variable name

// Get booking details before updating
$get_booking_sql = "SELECT room_type, room_id FROM bookings WHERE id = ? AND user_id = ?";
$get_stmt = $conn->prepare($get_booking_sql);

if ($get_stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$get_stmt->bind_param("ii", $booking_id, $user_id);
$get_stmt->execute();
$get_result = $get_stmt->get_result();

if($get_result->num_rows == 0) {
    // Booking doesn't exist or doesn't belong to user
    header("Location: dashboard.php");
    exit;
}

// Get the room type and room id
$booking_data = $get_result->fetch_assoc();
$room_type = $booking_data['room_type'];
$room_id = $booking_data['room_id'];
$get_stmt->close();

// Update booking status to cancelled
$sql = "UPDATE bookings SET status = 'cancelled' WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $booking_id);
$result = $stmt->execute();
$stmt->close();

if($result) {
    // Update room status back to available
    $table_name = "";
    
    switch($room_type) {
        case 'private':
            $table_name = "private_rooms";
            break;
        case 'hotdesk':
            $table_name = "hotdesk_rooms";
            break;
        case 'meeting':
            $table_name = "meeting_rooms";
            break;
    }
    
    if(!empty($table_name)) {
        $update_room_sql = "UPDATE {$table_name} SET status = 'available' WHERE id = ?";
        $update_room_stmt = $conn->prepare($update_room_sql);
        
        if ($update_room_stmt !== false) {
            $update_room_stmt->bind_param("i", $room_id);
            $update_room_stmt->execute();
            $update_room_stmt->close();
        }
    }
    
    // Success
    header("Location: dashboard.php?msg=cancelled");
} else {
    // Error
    header("Location: dashboard.php?error=1");
}

$conn->close();
?>
