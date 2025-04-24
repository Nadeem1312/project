<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php?redirect=virtual_spaces.php");
    exit;
}

// Include database connection
require_once 'db_connect.php';

// Get user information
$user_id = $_SESSION["user_id"];
$username = $_SESSION["fullname"] ?? $_SESSION["username"];

// Get available virtual rooms
$sql = "SELECT * FROM virtual_rooms ORDER BY name";
$result = $conn->query($sql);

$rooms = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
} else {
    // If no rooms exist in the database, create default rooms
    $default_rooms = [
        ['name' => 'Brainstorm Hub', 'capacity' => 8, 'description' => 'Creative space for brainstorming sessions', 'environment' => 'modern_office'],
        ['name' => 'Focus Zone', 'capacity' => 4, 'description' => 'Quiet space for focused discussions', 'environment' => 'minimal'],
        ['name' => 'Collaboration Corner', 'capacity' => 12, 'description' => 'Large space for team collaboration', 'environment' => 'creative_studio'],
        ['name' => 'Coffee Chat', 'capacity' => 6, 'description' => 'Casual space for informal meetings', 'environment' => 'cafe']
    ];
    
    foreach ($default_rooms as $room) {
        $insert_sql = "INSERT INTO virtual_rooms (name, capacity, description, environment) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        if ($stmt) {
            $stmt->bind_param("siss", $room['name'], $room['capacity'], $room['description'], $room['environment']);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Fetch the newly created rooms
    $result = $conn->query("SELECT * FROM virtual_rooms ORDER BY name");
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
    }
}

// Get room ID from URL if joining a specific room
$join_room_id = isset($_GET['room']) ? intval($_GET['room']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Spaces - WorkingSphere 360</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- PeerJS for WebRTC -->
    <script src="https://unpkg.com/peerjs@1.4.7/dist/peerjs.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
    <style>
        .virtual-spaces-container {
            padding: 3rem 0;
            background-color: var(--light-gray);
            min-height: calc(100vh - 200px);
        }
        
        .virtual-spaces-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .virtual-spaces-header h1 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        
        .virtual-spaces-header p {
            color: var(--gray-color);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .room-selection {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .room-option {
            background-color: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            width: 180px;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
            border: 3px solid transparent;
            position: relative;
        }

        .room-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .room-option.active {
            border-color: var(--primary-color);
        }

        .room-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(var(--primary-color-rgb), 0.2), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .room-option:hover::before {
            opacity: 1;
        }

        .room-option::after {
            content: 'Join Room';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .room-option:hover::after {
            opacity: 1;
        }

        .room-option-image {
            height: 100px;
            overflow: hidden;
            position: relative;
        }

        .room-option-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .room-option:hover .room-option-image img {
            transform: scale(1.1);
        }
        
        .room-option-details {
            padding: 1rem;
        }
        
        .room-option-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .room-option-capacity {
            font-size: 0.8rem;
            color: var(--gray-color);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .room-option-capacity i {
            margin-right: 0.3rem;
        }
        
        .virtual-space-container {
            background-color: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
            position: relative;
        }
        
        .virtual-space-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
        }
        
        .virtual-space-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .virtual-space-controls {
            display: flex;
            gap: 1rem;
        }
        
        .control-btn {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .control-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
        
        .control-btn.leave {
            background-color: #dc3545;
        }
        
        .control-btn.leave:hover {
            background-color: #bd2130;
        }
        
        .virtual-space {
            position: relative;
            height: 600px;
            overflow: hidden;
            background-size: cover;
            background-position: center;
            animation: ambientMovement 120s infinite alternate ease-in-out;
        }

        @keyframes ambientMovement {
            0% { background-position: center; }
            25% { background-position: left center; }
            50% { background-position: center bottom; }
            75% { background-position: right center; }
            100% { background-position: center top; }
        }
        
        .environment-modern_office {
            background-image: url('images/virtual/modern_office.jpg');
        }
        
        .environment-minimal {
            background-image: url('images/virtual/minimal.jpg');
        }
        
        .environment-creative_studio {
            background-image: url('images/virtual/creative_studio.jpg');
        }
        
        .environment-cafe {
            background-image: url('images/virtual/cafe.jpg');
        }
        
        .user-avatar {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
            cursor: move;
            user-select: none;
            z-index: 10;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            transition: box-shadow 0.3s ease, transform 0.3s ease;
            animation: float 3s infinite alternate ease-in-out;
        }

        .user-avatar:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transform: scale(1.05);
        }

        @keyframes float {
            0% { transform: translateY(0); }
            100% { transform: translateY(-5px); }
        }

        .user-avatar.speaking {
            box-shadow: 0 0 0 3px #28a745, 0 3px 10px rgba(0, 0, 0, 0.2);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7), 0 3px 10px rgba(0, 0, 0, 0.2); }
            70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0), 0 3px 10px rgba(0, 0, 0, 0.2); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0), 0 3px 10px rgba(0, 0, 0, 0.2); }
        }
        
        .user-avatar.self {
            background-color: var(--secondary-color);
        }
        
        .user-avatar.speaking {
            box-shadow: 0 0 0 3px #28a745, 0 3px 10px rgba(0, 0, 0, 0.2);
        }
        
        .user-avatar.muted::after {
            content: '\f131';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            bottom: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .user-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
        }
        
        .conversation-zone {
            position: absolute;
            border: 2px dashed rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            background-color: rgba(0, 0, 0, 0.1);
            pointer-events: none;
            z-index: 5;
            transition: all 0.5s ease;
        }

        .conversation-zone.active {
            background-color: rgba(40, 167, 69, 0.1);
            border-color: rgba(40, 167, 69, 0.5);
            animation: zoneActivate 2s infinite alternate;
        }

        @keyframes zoneActivate {
            0% { box-shadow: 0 0 20px rgba(40, 167, 69, 0.2); }
            100% { box-shadow: 0 0 40px rgba(40, 167, 69, 0.4); }
        }
        
        .meeting-table {
            position: absolute;
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            z-index: 1;
        }
        
        .meeting-table.round {
            border-radius: 50%;
        }
        
        .chat-panel {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 300px;
            background-color: white;
            border-top-left-radius: var(--border-radius);
            box-shadow: -3px -3px 10px rgba(0, 0, 0, 0.1);
            z-index: 20;
            display: flex;
            flex-direction: column;
            height: 300px;
            transform: translateY(calc(100% - 40px));
            transition: transform 0.3s ease;
        }
        
        .chat-panel.open {
            transform: translateY(0);
        }
        
        .chat-header {
            padding: 0.8rem;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .chat-header h3 {
            margin: 0;
            font-size: 1rem;
        }
        
        .chat-messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1rem;
        }
        
        .chat-message {
            margin-bottom: 1rem;
        }
        
        .message-sender {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
        }
        
        .message-text {
            background-color: var(--light-gray);
            padding: 0.5rem 0.8rem;
            border-radius: 1rem;
            display: inline-block;
            max-width: 80%;
            font-size: 0.9rem;
        }
        
        .message-form {
            padding: 0.8rem;
            display: flex;
            gap: 0.5rem;
            border-top: 1px solid var(--light-gray);
        }
        
        .message-input {
            flex-grow: 1;
            padding: 0.8rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
        }
        
        .send-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            width: 40px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .send-btn:hover {
            background-color: var(--secondary-color);
        }
        
        .participants-panel {
            position: absolute;
            top: 0;
            right: 0;
            width: 250px;
            background-color: white;
            border-bottom-left-radius: var(--border-radius);
            box-shadow: -3px 3px 10px rgba(0, 0, 0, 0.1);
            z-index: 20;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
        }
        
        .participants-panel.open {
            transform: translateY(0);
        }
        
        .participants-toggle {
            position: absolute;
            bottom: -40px;
            right: 0;
            background-color: white;
            border: none;
            border-bottom-left-radius: var(--border-radius);
            border-bottom-right-radius: var(--border-radius);
            padding: 0.5rem 1rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .participants-toggle .count {
            background-color: var(--primary-color);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }
        
        .participants-list {
            max-height: 300px;
            overflow-y: auto;
            padding: 1rem;
        }
        
        .participant-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
            background-color: var(--light-gray);
        }
        
        .participant-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
            margin-right: 0.8rem;
        }
        
        .participant-avatar.self {
            background-color: var(--secondary-color);
        }
        
        .participant-info {
            flex-grow: 1;
        }
        
        .participant-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--dark-color);
        }
        
        .participant-status {
            font-size: 0.8rem;
            color: var(--gray-color);
        }
        
        .participant-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .participant-btn {
            background: none;
            border: none;
            color: var(--gray-color);
            cursor: pointer;
            transition: color 0.3s ease;
            font-size: 0.9rem;
        }
        
        .participant-btn:hover {
            color: var(--primary-color);
        }
        
        .audio-controls {
            position: absolute;
            bottom: 20px;
            left: 20px;
            display: flex;
            gap: 1rem;
            z-index: 20;
        }
        
        .audio-btn {
            background-color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease;
            color: var(--dark-color);
            font-size: 1.2rem;
        }
        
        .audio-btn:hover {
            background-color: var(--light-gray);
        }
        
        .audio-btn.mute.active {
            background-color: #dc3545;
            color: white;
        }
        
        .instructions {
            position: absolute;
            top: 20px;
            left: 20px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 1rem;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            max-width: 300px;
            z-index: 20;
            opacity: 0.9;
        }
        
        .instructions h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .instructions ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .instructions li {
            margin-bottom: 0.3rem;
        }
        
        .instructions .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .room-info {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            z-index: 20;
        }

        .speech-bubble {
            position: absolute;
            background-color: white;
            border-radius: 20px;
            padding: 8px 12px;
            font-size: 0.8rem;
            max-width: 150px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            z-index: 15;
            animation: fadeInOut 3s forwards;
            pointer-events: none;
        }

        .speech-bubble::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 20px;
            border-width: 10px 10px 0;
            border-style: solid;
            border-color: white transparent transparent;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(10px); }
            10% { opacity: 1; transform: translateY(0); }
            80% { opacity: 1; }
            100% { opacity: 0; }
        }
        
        @media (max-width: 992px) {
            .virtual-space {
                height: 500px;
            }
            
            .chat-panel {
                width: 250px;
            }
        }
        
        @media (max-width: 768px) {
            .virtual-space {
                height: 400px;
            }
            
            .chat-panel {
                width: 100%;
            }
            
            .participants-panel {
                width: 100%;
            }
            
            .instructions {
                max-width: 250px;
            }
        }

        @keyframes ambientMovement {
            0% { background-position: center; }
            25% { background-position: left center; }
            50% { background-position: center bottom; }
            75% { background-position: right center; }
            100% { background-position: center top; }
        }

        @keyframes float {
            0% { transform: translateY(0); }
            100% { transform: translateY(-5px); }
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7), 0 3px 10px rgba(0, 0, 0, 0.2); }
            70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0), 0 3px 10px rgba(0, 0, 0, 0.2); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0), 0 3px 10px rgba(0, 0, 0, 0.2); }
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
                    <li><a href="virtual_spaces.php" class="active">VIRTUAL SPACES</a></li>
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

    <!-- Virtual Spaces Section -->
    <section class="virtual-spaces-container">
        <div class="container">
            <div class="virtual-spaces-header">
                <h1>Virtual Meeting Spaces</h1>
                <p>Connect with colleagues in our immersive virtual meeting rooms. Move your avatar around to join conversations and collaborate in real-time.</p>
            </div>
            
            <!-- Room Selection -->
            <div class="room-selection" id="room-selection">
                <?php foreach($rooms as $room): ?>
                <div class="room-option" data-room-id="<?php echo $room['id']; ?>" data-environment="<?php echo htmlspecialchars($room['environment']); ?>">
                    <div class="room-option-image">
                        <img src="images/virtual/<?php echo htmlspecialchars($room['environment']); ?>.jpg" alt="<?php echo htmlspecialchars($room['name']); ?>">
                    </div>
                    <div class="room-option-details">
                        <div class="room-option-name"><?php echo htmlspecialchars($room['name']); ?></div>
                        <div class="room-option-capacity">
                            <i class="fas fa-users"></i> <?php echo $room['capacity']; ?> max
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Virtual Space -->
            <div class="virtual-space-container" id="virtual-space-container" style="display: none;">
                <div class="virtual-space-header">
                    <h2 id="room-name">Room Name</h2>
                    <div class="virtual-space-controls">
                        <button id="leave-room" class="control-btn leave" title="Leave Room">
                            <i class="fas fa-door-open"></i>
                        </button>
                    </div>
                </div>
                
                <div class="virtual-space" id="virtual-space">
                    <!-- Room info -->
                    <div class="room-info" id="room-info">
                        <i class="fas fa-info-circle"></i> <span id="room-capacity">0/0</span> participants
                    </div>
                    
                    <!-- Instructions -->
                    <div class="instructions" id="instructions">
                        <button class="close-btn" id="close-instructions">
                            <i class="fas fa-times"></i>
                        </button>
                        <h3>How to use this space:</h3>
                        <ul>
                            <li>Drag your avatar to move around</li>
                            <li>Move close to others to start a conversation</li>
                            <li>Only people in your conversation zone can hear you</li>
                            <li>Use the buttons at the bottom to control your audio</li>
                        </ul>
                    </div>
                    
                    <!-- Meeting tables -->
                    <div class="meeting-table round" style="width: 150px; height: 150px; top: 100px; left: 200px;"></div>
                    <div class="meeting-table" style="width: 250px; height: 120px; top: 300px; left: 400px;"></div>
                    <div class="meeting-table round" style="width: 100px; height: 100px; top: 400px; left: 150px;"></div>
                    
                    <!-- Conversation zones -->
                    <div class="conversation-zone" style="width: 250px; height: 250px; top: 50px; left: 150px;"></div>
                    <div class="conversation-zone" style="width: 300px; height: 300px; top: 250px; left: 375px;"></div>
                    <div class="conversation-zone" style="width: 200px; height: 200px; top: 350px; left: 100px;"></div>
                    
                    <!-- User avatar (self) -->
                    <div class="user-avatar self" id="self-avatar" style="top: 300px; left: 300px;">
                        <?php echo substr($username, 0, 1); ?>
                        <div class="user-label"><?php echo htmlspecialchars($username); ?> (You)</div>
                    </div>
                    
                    <!-- Audio controls -->
                    <div class="audio-controls">
                        <button class="audio-btn mute" id="toggle-mute" title="Toggle Mute">
                            <i class="fas fa-microphone"></i>
                        </button>
                    </div>
                    
                    <!-- Chat panel -->
                    <div class="chat-panel" id="chat-panel">
                        <div class="chat-header" id="chat-header">
                            <h3>Chat</h3>
                            <i class="fas fa-chevron-up"></i>
                        </div>
                        <div class="chat-messages" id="chat-messages">
                            <!-- Chat messages will be added here -->
                        </div>
                        <form class="message-form" id="message-form">
                            <input type="text" class="message-input" id="message-input" placeholder="Type a message...">
                            <button type="submit" class="send-btn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Participants panel -->
                    <div class="participants-panel" id="participants-panel">
                        <div class="participants-list" id="participants-list">
                            <!-- Participants will be added here -->
                        </div>
                        <button class="participants-toggle" id="participants-toggle">
                            <i class="fas fa-users"></i> Participants <span class="count" id="participants-count">0</span>
                        </button>
                    </div>
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
                        <li><a href="virtual_spaces.php" class="active">Virtual Spaces</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const menuToggle = document.querySelector('.menu-toggle');
            const navLinks = document.querySelector('nav ul');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    navLinks.classList.toggle('active');
                });
            }
            
            // Virtual Space functionality
            const userId = <?php echo $user_id; ?>;
            const username = "<?php echo addslashes($username); ?>";
            const joinRoomId = <?php echo $join_room_id; ?>;
            
            // Room selection
            const roomOptions = document.querySelectorAll('.room-option');
            const virtualSpaceContainer = document.getElementById('virtual-space-container');
            const roomSelection = document.getElementById('room-selection');
            const roomName = document.getElementById('room-name');
            const roomInfo = document.getElementById('room-info');
            const roomCapacity = document.getElementById('room-capacity');
            const virtualSpace = document.getElementById('virtual-space');
            const selfAvatar = document.getElementById('self-avatar');
            const leaveRoomBtn = document.getElementById('leave-room');
            
            // Chat functionality
            const chatPanel = document.getElementById('chat-panel');
            const chatHeader = document.getElementById('chat-header');
            const chatMessages = document.getElementById('chat-messages');
            const messageForm = document.getElementById('message-form');
            const messageInput = document.getElementById('message-input');
            
            // Participants panel
            const participantsPanel = document.getElementById('participants-panel');
            const participantsToggle = document.getElementById('participants-toggle');
            const participantsList = document.getElementById('participants-list');
            const participantsCount = document.getElementById('participants-count');
            
            // Audio controls
            const toggleMuteBtn = document.getElementById('toggle-mute');
            
            // Instructions
            const instructions = document.getElementById('instructions');
            const closeInstructionsBtn = document.getElementById('close-instructions');
            
            // Current room data
            let currentRoom = null;
            let participants = [];
            let isMuted = false;
            let peer = null;
            let connections = {};
            let currentConversationZone = null;
            let audioStream = null;
            
            // Initialize PeerJS
            function initializePeerJS() {
                // Generate a unique ID based on user ID and timestamp
                const peerId = `user_${userId}_${Date.now()}`;
                
                peer = new Peer(peerId, {
                    debug: 2
                });
                
                peer.on('open', function(id) {
                    console.log('My peer ID is: ' + id);
                    
                    // Broadcast join message to other users in the room
                    fetch('virtual_room_api.php?action=get_peers', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'room_id=' + currentRoom.id
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.peers) {
                            // Connect to each peer
                            data.peers.forEach(peerInfo => {
                                if (peerInfo.peer_id && peerInfo.peer_id !== id) {
                                    const conn = peer.connect(peerInfo.peer_id);
                                    
                                    conn.on('open', function() {
                                        // Send join message
                                        conn.send({
                                            type: 'join',
                                            user: {
                                                id: userId,
                                                name: username,
                                                position: {
                                                    x: parseInt(selfAvatar.style.left),
                                                    y: parseInt(selfAvatar.style.top)
                                                },
                                                muted: isMuted
                                            }
                                        });
                                        
                                        // Store connection
                                        connections[conn.peer] = conn;
                                    });
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error getting peers:', error);
                    });
                });
                
                peer.on('connection', function(conn) {
                    handleConnection(conn);
                });
                
                peer.on('call', function(call) {
                    // Answer the call with our stream
                    if (audioStream) {
                        call.answer(audioStream);
                    } else {
                        call.answer();
                    }
                    
                    call.on('stream', function(remoteStream) {
                        // Create audio element for remote stream
                        const audio = document.createElement('audio');
                        audio.srcObject = remoteStream;
                        audio.id = `audio-${call.peer}`;
                        audio.autoplay = true;
                        document.body.appendChild(audio);
                    });
                });
                
                // Get user audio
                navigator.mediaDevices.getUserMedia({ audio: true, video: false })
                    .then(function(stream) {
                        audioStream = stream;
                        
                        // Set up audio level detection
                        const audioContext = new AudioContext();
                        const analyser = audioContext.createAnalyser();
                        const microphone = audioContext.createMediaStreamSource(stream);
                        const scriptProcessor = audioContext.createScriptProcessor(2048, 1, 1);
                        
                        analyser.smoothingTimeConstant = 0.8;
                        analyser.fftSize = 1024;
                        
                        microphone.connect(analyser);
                        analyser.connect(scriptProcessor);
                        scriptProcessor.connect(audioContext.destination);
                        
                        scriptProcessor.onaudioprocess = function() {
                            const array = new Uint8Array(analyser.frequencyBinCount);
                            analyser.getByteFrequencyData(array);
                            const arraySum = array.reduce((a, value) => a + value, 0);
                            const average = arraySum / array.length;
                            
                            // If speaking and not muted, show speaking indicator
                            if (average > 20 && !isMuted) {
                                selfAvatar.classList.add('speaking');
                                // Broadcast speaking status
                                broadcastMessage({
                                    type: 'speaking',
                                    speaking: true
                                });
                            } else {
                                selfAvatar.classList.remove('speaking');
                                // Broadcast not speaking status
                                broadcastMessage({
                                    type: 'speaking',
                                    speaking: false
                                });
                            }
                        };
                    })
                    .catch(function(err) {
                        console.log('Failed to get local stream', err);
                        alert('Could not access microphone. Please check your permissions.');
                    });
            }
            
            // Handle new peer connection
            function handleConnection(conn) {
                connections[conn.peer] = conn;
                
                conn.on('data', function(data) {
                    handlePeerMessage(conn.peer, data);
                });
                
                conn.on('close', function() {
                    delete connections[conn.peer];
                });
            }
            
            // Handle peer message
            function handlePeerMessage(peerId, data) {
                switch (data.type) {
                    case 'join':
                        // Add participant
                        addParticipant(data.user);
                        // Send our info to the new participant
                        sendToPeer(peerId, {
                            type: 'user_info',
                            user: {
                                id: userId,
                                name: username,
                                position: {
                                    x: parseInt(selfAvatar.style.left),
                                    y: parseInt(selfAvatar.style.top)
                                },
                                muted: isMuted
                            }
                        });
                        break;
                    
                    case 'user_info':
                        // Add participant
                        addParticipant(data.user);
                        break;
                    
                    case 'move':
                        // Update participant position
                        updateParticipantPosition(data.userId, data.position);
                        break;
                    
                    case 'speaking':
                        // Update participant speaking status
                        updateParticipantSpeaking(data.userId, data.speaking);
                        break;
                    
                    case 'mute':
                        // Update participant mute status
                        updateParticipantMute(data.userId, data.muted);
                        break;
                    
                    case 'chat':
                        // Add chat message
                        addChatMessage(data.sender, data.message, false);
                        break;
                    
                    case 'leave':
                        // Remove participant
                        removeParticipant(data.userId);
                        break;
                }
            }
            
            // Send message to specific peer
            function sendToPeer(peerId, data) {
                if (connections[peerId]) {
                    connections[peerId].send(data);
                }
            }
            
            // Broadcast message to all peers
            function broadcastMessage(data) {
                for (const peerId in connections) {
                    sendToPeer(peerId, data);
                }
            }
            
            // Join room
            function joinRoom(roomId) {
                // Find room data
                const rooms = <?php echo json_encode($rooms); ?>;
                const room = rooms.find(r => r.id == roomId);
                
                if (!room) {
                    alert('Room not found');
                    return;
                }
                
                currentRoom = room;
                
                // Update UI
                roomName.textContent = room.name;
                virtualSpace.className = 'virtual-space environment-' + room.environment;
                roomCapacity.textContent = '1/' + room.capacity + ' (Live Users)';
                
                // Show virtual space and hide room selection
                virtualSpaceContainer.style.display = 'block';
                roomSelection.style.display = 'none';
                
                // Initialize PeerJS
                initializePeerJS();
                
                // Add self to participants
                addParticipant({
                    id: userId,
                    name: username,
                    position: {
                        x: parseInt(selfAvatar.style.left),
                        y: parseInt(selfAvatar.style.top)
                    },
                    muted: isMuted,
                    isSelf: true
                });
                
                // Add welcome message
                addChatMessage('System', `Welcome to ${room.name}! Move around to join conversations.`, true);
                
                // Update URL
                history.pushState({}, '', 'virtual_spaces.php?room=' + roomId);
                
                // Record join in database
                fetch('virtual_room_api.php?action=join_room', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'room_id=' + roomId
                });
            }
            
            // Leave room
            function leaveRoom() {
                // Hide virtual space and show room selection
                virtualSpaceContainer.style.display = 'none';
                roomSelection.style.display = 'flex';
                
                // Broadcast leave message
                broadcastMessage({
                    type: 'leave',
                    userId: userId
                });
                
                // Close all connections
                for (const peerId in connections) {
                    connections[peerId].close();
                }
                
                // Close peer connection
                if (peer) {
                    peer.destroy();
                }
                
                // Stop audio stream
                if (audioStream) {
                    audioStream.getTracks().forEach(track => track.stop());
                }
                
                // Remove all audio elements
                document.querySelectorAll('audio').forEach(audio => audio.remove());
                
                // Update URL
                history.pushState({}, '', 'virtual_spaces.php');
                
                // Record leave in database
                if (currentRoom) {
                    fetch('virtual_room_api.php?action=leave_room', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'room_id=' + currentRoom.id
                    });
                }
                
                // Reset variables
                currentRoom = null;
                participants = [];
                connections = {};
                currentConversationZone = null;
            }
            
            // Add participant
            function addParticipant(user) {
                // Check if participant already exists
                if (participants.some(p => p.id === user.id)) {
                    return;
                }
                
                // Add to participants array
                participants.push(user);
                
                // Update participants count
                participantsCount.textContent = participants.length;
                
                // Update room capacity
                roomCapacity.textContent = participants.length + '/' + currentRoom.capacity;
                
                // Add to participants list
                const participantItem = document.createElement('div');
                participantItem.className = 'participant-item';
                participantItem.dataset.id = user.id;
                
                const participantAvatar = document.createElement('div');
                participantAvatar.className = 'participant-avatar' + (user.isSelf ? ' self' : '');
                participantAvatar.textContent = user.name.charAt(0);
                
                const participantInfo = document.createElement('div');
                participantInfo.className = 'participant-info';
                
                const participantName = document.createElement('div');
                participantName.className = 'participant-name';
                participantName.textContent = user.name + (user.isSelf ? ' (You)' : '');
                
                const participantStatus = document.createElement('div');
                participantStatus.className = 'participant-status';
                participantStatus.textContent = user.muted ? 'Muted' : 'Not speaking';
                
                participantInfo.appendChild(participantName);
                participantInfo.appendChild(participantStatus);
                
                participantItem.appendChild(participantAvatar);
                participantItem.appendChild(participantInfo);
                
                if (!user.isSelf) {
                    // Add avatar to virtual space
                    const avatar = document.createElement('div');
                    avatar.className = 'user-avatar' + (user.muted ? ' muted' : '');
                    avatar.id = 'avatar-' + user.id;
                    avatar.style.left = user.position.x + 'px';
                    avatar.style.top = user.position.y + 'px';
                    avatar.textContent = user.name.charAt(0);
                    
                    const label = document.createElement('div');
                    label.className = 'user-label';
                    label.textContent = user.name;
                    
                    avatar.appendChild(label);
                    virtualSpace.appendChild(avatar);
                    
                    // Add actions to participant item
                    const participantActions = document.createElement('div');
                    participantActions.className = 'participant-actions';
                    
                    const muteBtn = document.createElement('button');
                    muteBtn.className = 'participant-btn';
                    muteBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
                    muteBtn.title = 'Mute for you';
                    
                    const messageBtn = document.createElement('button');
                    messageBtn.className = 'participant-btn';
                    messageBtn.innerHTML = '<i class="fas fa-comment"></i>';
                    messageBtn.title = 'Send private message';
                    
                    participantActions.appendChild(muteBtn);
                    participantActions.appendChild(messageBtn);
                    participantItem.appendChild(participantActions);
                }
                
                participantsList.appendChild(participantItem);
                
                // Add system message
                addChatMessage('System', user.isSelf ? 'You joined the room.' : `${user.name} joined the room.`, true);
            }
            
            // Update participant position with smooth animation
            function updateParticipantPosition(userId, position) {
                const avatar = document.getElementById('avatar-' + userId);
                if (avatar) {
                    // Animate the movement
                    $(avatar).animate({
                        left: position.x + 'px',
                        top: position.y + 'px'
                    }, 500, 'easeOutQuad');
                }
                
                // Update participant in array
                const participant = participants.find(p => p.id === userId);
                if (participant) {
                    participant.position = position;
                }
                
                // Check conversation zones
                checkConversationZones();
            }

            // Update participant speaking status with visual feedback
            function updateParticipantSpeaking(userId, speaking) {
                const avatar = document.getElementById('avatar-' + userId);
                if (avatar) {
                    if (speaking) {
                        avatar.classList.add('speaking');
                        
                        // Create speech bubble for visual feedback
                        if (Math.random() > 0.7) { // Only show speech bubbles occasionally
                            const bubble = document.createElement('div');
                            bubble.className = 'speech-bubble';
                            bubble.style.left = (parseInt(avatar.style.left) + 30) + 'px';
                            bubble.style.top = (parseInt(avatar.style.top) - 60) + 'px';
                            
                            // Random conversation snippets
                            const snippets = [
                                "Great idea!",
                                "I agree with that.",
                                "Let's discuss that further.",
                                "What do you think?",
                                "Interesting point.",
                                "I'm not sure about that.",
                                "Could you explain more?"
                            ];
                            
                            bubble.textContent = snippets[Math.floor(Math.random() * snippets.length)];
                            virtualSpace.appendChild(bubble);
                            
                            // Remove bubble after animation completes
                            setTimeout(() => {
                                bubble.remove();
                            }, 3000);
                        }
                    } else {
                        avatar.classList.remove('speaking');
                    }
                }
                
                // Update participant status in list
                const participantItem = document.querySelector(`.participant-item[data-id="${userId}"]`);
                if (participantItem) {
                    const status = participantItem.querySelector('.participant-status');
                    if (status) {
                        status.textContent = speaking ? 'Speaking' : 'Not speaking';
                    }
                }
            }
            
            // Update participant mute status
            function updateParticipantMute(userId, muted) {
                const avatar = document.getElementById('avatar-' + userId);
                if (avatar) {
                    if (muted) {
                        avatar.classList.add('muted');
                    } else {
                        avatar.classList.remove('muted');
                    }
                }
                
                // Update participant in array
                const participant = participants.find(p => p.id === userId);
                if (participant) {
                    participant.muted = muted;
                }
                
                // Update participant status in list
                const participantItem = document.querySelector(`.participant-item[data-id="${userId}"]`);
                if (participantItem) {
                    const status = participantItem.querySelector('.participant-status');
                    if (status) {
                        status.textContent = muted ? 'Muted' : 'Not speaking';
                    }
                }
            }
            
            // Remove participant
            function removeParticipant(userId) {
                // Remove from participants array
                participants = participants.filter(p => p.id !== userId);
                
                // Update participants count
                participantsCount.textContent = participants.length;
                
                // Update room capacity
                roomCapacity.textContent = participants.length + '/' + currentRoom.capacity;
                
                // Remove from participants list
                const participantItem = document.querySelector(`.participant-item[data-id="${userId}"]`);
                if (participantItem) {
                    participantItem.remove();
                }
                
                // Remove avatar from virtual space
                const avatar = document.getElementById('avatar-' + userId);
                if (avatar) {
                    avatar.remove();
                }
                
                // Remove audio element
                const audio = document.getElementById('audio-' + userId);
                if (audio) {
                    audio.remove();
                }
                
                // Add system message
                const participant = participants.find(p => p.id === userId);
                addChatMessage('System', `${participant ? participant.name : 'Someone'} left the room.`, true);
            }
            
            // Add chat message
            function addChatMessage(sender, message, isSystem) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'chat-message';
                
                const senderDiv = document.createElement('div');
                senderDiv.className = 'message-sender';
                senderDiv.textContent = sender;
                
                const textDiv = document.createElement('div');
                textDiv.className = 'message-text';
                textDiv.textContent = message;
                
                messageDiv.appendChild(senderDiv);
                messageDiv.appendChild(textDiv);
                
                chatMessages.appendChild(messageDiv);
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Flash chat header if closed
                if (!chatPanel.classList.contains('open')) {
                    chatHeader.style.backgroundColor = '#dc3545';
                    setTimeout(() => {
                        chatHeader.style.backgroundColor = '';
                    }, 1000);
                }
            }
            
            // Check conversation zones
            function checkConversationZones() {
                const selfX = parseInt(selfAvatar.style.left) + 30; // Center point
                const selfY = parseInt(selfAvatar.style.top) + 30; // Center point
                
                // Check all conversation zones
                const zones = document.querySelectorAll('.conversation-zone');
                let inZone = false;
                
                zones.forEach(zone => {
                    const zoneX = parseInt(zone.style.left) + parseInt(zone.style.width) / 2;
                    const zoneY = parseInt(zone.style.top) + parseInt(zone.style.height) / 2;
                    const zoneRadius = parseInt(zone.style.width) / 2;
                    
                    // Calculate distance from center of zone
                    const distance = Math.sqrt(Math.pow(selfX - zoneX, 2) + Math.pow(selfY - zoneY, 2));
                    
                    if (distance <= zoneRadius) {
                        // In this zone
                        inZone = true;
                        
                        // If not already in this zone
                        if (currentConversationZone !== zone) {
                            // Leave previous zone
                            if (currentConversationZone) {
                                currentConversationZone.classList.remove('active');
                            }
                            
                            // Join new zone
                            zone.classList.add('active');
                            currentConversationZone = zone;
                            
                            // Update audio connections based on who's in this zone
                            updateAudioConnections();
                        }
                    }
                });
                
                // If not in any zone
                if (!inZone && currentConversationZone) {
                    currentConversationZone.classList.remove('active');
                    currentConversationZone = null;
                    
                    // Update audio connections
                    updateAudioConnections();
                }
            }
            
            // Update audio connections based on conversation zones
            function updateAudioConnections() {
                // TODO: In a real implementation, you would connect only to users in the same zone
                // For this demo, we'll just simulate it
            }
            
            // Make self avatar draggable with jQuery UI
            function makeDraggable(element) {
                $(element).draggable({
                    containment: "parent",
                    start: function() {
                        $(this).addClass("dragging");
                    },
                    drag: function(event, ui) {
                        // Broadcast position update
                        broadcastMessage({
                            type: 'move',
                            userId: userId,
                            position: {
                                x: ui.position.left,
                                y: ui.position.top
                            }
                        });
                        
                        // Check conversation zones
                        checkConversationZones();
                    },
                    stop: function() {
                        $(this).removeClass("dragging");
                        
                        // Save position to server
                        fetch('virtual_room_api.php?action=update_position', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'room_id=' + currentRoom.id + 
                                  '&position_x=' + parseInt(this.style.left) + 
                                  '&position_y=' + parseInt(this.style.top)
                        });
                    }
                });
            }
            
            // Initialize draggable avatar
            makeDraggable(selfAvatar);
            
            // Room selection event listeners
            roomOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const roomId = this.getAttribute('data-room-id');
                    joinRoom(roomId);
                });
            });
            
            // Leave room button
            leaveRoomBtn.addEventListener('click', function() {
                leaveRoom();
            });
            
            // Toggle mute button
            toggleMuteBtn.addEventListener('click', function() {
                isMuted = !isMuted;
                
                if (isMuted) {
                    this.innerHTML = '<i class="fas fa-microphone-slash"></i>';
                    this.classList.add('active');
                    selfAvatar.classList.add('muted');
                } else {
                    this.innerHTML = '<i class="fas fa-microphone"></i>';
                    this.classList.remove('active');
                    selfAvatar.classList.remove('muted');
                }
                
                // Mute/unmute audio stream
                if (audioStream) {
                    audioStream.getAudioTracks().forEach(track => {
                        track.enabled = !isMuted;
                    });
                }
                
                // Broadcast mute status
                broadcastMessage({
                    type: 'mute',
                    userId: userId,
                    muted: isMuted
                });
            });
            
            // Chat panel toggle
            chatHeader.addEventListener('click', function() {
                chatPanel.classList.toggle('open');
                chatHeader.style.backgroundColor = ''; // Reset color
            });
            
            // Chat message form
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const message = messageInput.value.trim();
                if (message) {
                    // Add message to chat
                    addChatMessage(username, message, false);
                    
                    // Broadcast message
                    broadcastMessage({
                        type: 'chat',
                        sender: username,
                        message: message
                    });
                    
                    // Clear input
                    messageInput.value = '';
                }
            });
            
            // Participants panel toggle
            participantsToggle.addEventListener('click', function() {
                participantsPanel.classList.toggle('open');
            });
            
            // Close instructions
            closeInstructionsBtn.addEventListener('click', function() {
                instructions.style.display = 'none';
            });
            
            // If room ID is provided in URL, join that room
            if (joinRoomId > 0) {
                joinRoom(joinRoomId);
            }
        });
    </script>
</body>
</html>
