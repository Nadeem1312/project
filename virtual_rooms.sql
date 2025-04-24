-- Create virtual_rooms table
CREATE TABLE IF NOT EXISTS virtual_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    capacity INT NOT NULL DEFAULT 8,
    description TEXT,
    environment VARCHAR(50) NOT NULL DEFAULT 'modern_office',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create virtual_room_sessions table
CREATE TABLE IF NOT EXISTS virtual_room_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    join_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (room_id) REFERENCES virtual_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create virtual_room_messages table
CREATE TABLE IF NOT EXISTS virtual_room_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    sender_id INT NOT NULL,
    recipient_id INT NULL,
    message TEXT NOT NULL,
    is_private TINYINT(1) NOT NULL DEFAULT 0,
    sent_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES virtual_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default virtual rooms
INSERT INTO virtual_rooms (name, capacity, description, environment) VALUES
('Brainstorm Hub', 8, 'Creative space for brainstorming sessions', 'modern_office'),
('Focus Zone', 4, 'Quiet space for focused discussions', 'minimal'),
('Collaboration Corner', 12, 'Large space for team collaboration', 'creative_studio'),
('Coffee Chat', 6, 'Casual space for informal meetings', 'cafe');
