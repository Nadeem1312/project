<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Include database connection
require_once 'db_connect.php';

// Get user ID from session
$user_id = $_SESSION["user_id"];

// Get action from request
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle different actions
switch ($action) {
    case 'join_room':
        joinRoom();
        break;
    case 'leave_room':
        leaveRoom();
        break;
    case 'update_position':
        updatePosition();
        break;
    case 'send_message':
        sendMessage();
        break;
    case 'get_peers':
        getPeers();
        break;
    case 'update_peer_id':
        updatePeerId();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

// Function to join a room
function joinRoom() {
    global $conn, $user_id;
    
    // Get room ID from request
    $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
    
    if ($room_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid room ID']);
        exit;
    }
    
    // Check if room exists
    $check_sql = "SELECT id, capacity FROM virtual_rooms WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if ($check_stmt === false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    
    $check_stmt->bind_param("i", $room_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Room not found']);
        exit;
    }
    
    $room = $check_result->fetch_assoc();
    $check_stmt->close();
    
    // Check if room is full
    $count_sql = "SELECT COUNT(*) as user_count FROM virtual_room_sessions 
                 WHERE room_id = ? AND last_active > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
    $count_stmt = $conn->prepare($count_sql);
    
    if ($count_stmt === false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    
    $count_stmt->bind_param("i", $room_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $count_stmt->close();
    
    if ($count_row['user_count'] >= $room['capacity']) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Room is full']);
        exit;
    }
    
    // Check if user is already in a room
    $check_session_sql = "SELECT id, room_id FROM virtual_room_sessions 
                         WHERE user_id = ? AND last_active > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
    $check_session_stmt = $conn->prepare($check_session_sql);
    
    if ($check_session_stmt === false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    
    $check_session_stmt->bind_param("i", $user_id);
    $check_session_stmt->execute();
    $check_session_result = $check_session_stmt->get_result();
    
    if ($check_session_result->num_rows > 0) {
        $session = $check_session_result->fetch_assoc();
        
        // If user is already in this room, just update last_active
        if ($session['room_id'] == $room_id) {
            $update_sql = "UPDATE virtual_room_sessions SET last_active = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            
            if ($update_stmt === false) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Database error']);
                exit;
            }
            
            $update_stmt->bind_param("i", $session['id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'room_id' => $room_id]);
            exit;
        }
        
        // If user is in another room, leave that room first
        $leave_sql = "UPDATE virtual_room_sessions SET active = 0 WHERE id = ?";
        $leave_stmt = $conn->prepare($leave_sql);
        
        if ($leave_stmt === false) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Database error']);
            exit;
        }
        
        $leave_stmt->bind_param("i", $session['id']);
        $leave_stmt->execute();
        $leave_stmt->close();
    }
    
    $check_session_stmt->close();
    
    // Join the room
    $join_sql = "INSERT INTO virtual_room_sessions (user_id, room_id, join_time, last_active, active, position_x, position_y) 
                VALUES (?, ?, NOW(), NOW(), 1, 300, 300)";
    $join_stmt = $conn->prepare($join_sql);
    
    if ($join_stmt === false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    
    $join_stmt->bind_param("ii", $user_id, $room_id);
    $join_result = $join_stmt->execute();
    $join_stmt->close();
    
    if ($join_result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'room_id' => $room_id]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to join room']);
    }
}

// Function to leave a room
function leaveRoom() {
    global $conn, $user_id;
    
    // Get room ID from request
    $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
    
    if ($room_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid room ID']);
        exit;
    }
    
    // Update session to inactive
    $leave_sql = "UPDATE virtual_room_sessions SET active = 0 
                 WHERE user_id = ? AND room_id = ? AND active = 1";
    $leave_stmt = $conn->prepare($leave_sql);
    
    if ($leave_stmt === false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    
    $leave_stmt->bind_param("ii", $user_id, $room_id);
    $leave_result = $leave_stmt->execute();
    $leave_stmt->close();
    
    if ($leave_result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to leave room']);
    }
}

// Function to update user position
function updatePosition() {
    global $conn, $user_id;
    
    // Get parameters from request
    $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
    $position_x = isset($_POST['position_x']) ? intval($_POST['position_x']) : 0;
    $position_y = isset($_POST['position_y']) ? intval($_POST['position_y']) : 0;
    
    if ($room_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid room ID']);
        exit;
    }
    
    // Update position
    $sql = "UPDATE virtual_room_sessions SET position_x = ?, position_y = ?, last_active = NOW() 
           WHERE user_id = ? AND room_id = ? AND active = 1";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    
    $stmt->bind_param("iiii", $position_x, $position_y, $user_id, $room_id);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to update position']);
    }
}

// Function to send a message
function sendMessage() {
    global $conn, $user_id;
    
    // Get parameters from request
    $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    if ($room_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid room ID']);
        exit;
    }
    
    if (empty($message)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Message cannot be empty']);
        exit;
    }
    
    // Check if user is in the room
    $check_sql = "SELECT id FROM virtual_room_sessions 
                 WHERE user_id = ? AND room_id = ? AND active = 1 
                 AND last_active > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
    $check_stmt = $conn->prepare($check_sql);
    
    if ($check_stmt === false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    
    $check_stmt->bind_param("ii", $user_id, $room_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'You are not in this room']);
        exit;
    }
    
    $check_stmt->close();
    
    // Send message
    $sql = "INSERT INTO virtual_room_messages (room_id, sender_id, message, sent_time) 
           VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    
    $stmt->bind_param("iis", $room_id, $user_id, $message);
    $result = $stmt->execute();
    $message_id = $stmt->insert_id;
    $stmt->close();
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message_id' => $message_id]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to send message']);
    }
}

// Function to get peers in a room
function getPeers() {
    global $conn, $user_id;
    
    // Get room ID from request
    $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
    
    if ($room_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid room ID']);
        exit;
    }
    
    // Get active peers
    $sql = "SELECT user_id, peer_id FROM virtual_room_sessions 
           WHERE room_id = ? AND active = 1 AND peer_id IS NOT NULL
           AND last_active > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
           AND user_id != ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $peers = [];
    while ($row = $result->fetch_assoc()) {
        $peers[] = [
            'user_id' => $row['user_id'],
            'peer_id' => $row['peer_id']
        ];
    }
    
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'peers' => $peers]);
}

// Function to update peer ID
function updatePeerId() {
    global $conn, $user_id;
    
    // Get parameters from request
    $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
    $peer_id = isset($_POST['peer_id']) ? $_POST['peer_id'] : '';
    
    if ($room_id <= 0 || empty($peer_id)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }
    
    // Update peer ID
    $sql = "UPDATE virtual_room_sessions SET peer_id = ?, last_active = NOW() 
           WHERE user_id = ? AND room_id = ? AND active = 1";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    
    $stmt->bind_param("sii", $peer_id, $user_id, $room_id);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to update peer ID']);
    }
}
?>
