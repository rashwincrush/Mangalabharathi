<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';

// Drop existing events table
$drop_table = "DROP TABLE IF EXISTS events";
if (!$conn->query($drop_table)) {
    die("Error dropping events table: " . $conn->error);
}

// Create events table with new structure
$events_table = "CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE,
    category VARCHAR(50) DEFAULT 'General',
    location VARCHAR(255),
    status ENUM('draft', 'published', 'past') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$conn->query($events_table)) {
    die("Error creating events table: " . $conn->error);
}

// Add sample event
$sample_event = "INSERT INTO events (title, description, event_date, category, location, status) VALUES 
    ('Annual Charity Gala', 
     'Join us for our annual fundraising gala. An evening of music, dinner, and making a difference.', 
     '2025-03-15', 
     'Fundraising', 
     'Grand Ballroom, Hotel Taj, Bangalore', 
     'published')";

if (!$conn->query($sample_event)) {
    die("Error adding sample event: " . $conn->error);
}

echo "Events table recreated successfully with sample data!";
?>
