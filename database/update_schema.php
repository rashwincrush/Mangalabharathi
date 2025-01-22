<?php
require_once '../includes/config.php';

// Get database connection
$conn = get_db_connection();

// Array to store errors
$errors = [];

try {
    // Create events table if it doesn't exist
    $createEventsTable = "CREATE TABLE IF NOT EXISTS `events` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `description` text NOT NULL,
        `event_date` date NOT NULL,
        `location` varchar(255),
        `image` varchar(255),
        `category` varchar(50) NOT NULL,
        `status` enum('upcoming', 'past', 'journey') NOT NULL DEFAULT 'upcoming',
        `people_helped` int DEFAULT NULL,
        `volunteers` int DEFAULT NULL,
        `budget` decimal(10,2) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (!$conn->query($createEventsTable)) {
        $errors[] = "Error creating events table: " . $conn->error;
    }

    // Create event_media table
    $createEventMediaTable = "CREATE TABLE IF NOT EXISTS `event_media` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `event_id` int(11) NOT NULL,
        `file_path` varchar(255) NOT NULL,
        `media_type` ENUM('image', 'document') NOT NULL,
        `file_name` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (!$conn->query($createEventMediaTable)) {
        $errors[] = "Error creating event_media table: " . $conn->error;
    }

    // Insert sample journey posts
    $samplePosts = [
        [
            'title' => 'First Medical Camp',
            'description' => 'Our first major medical camp serving the community',
            'event_date' => '2023-06-10',
            'location' => 'T.Nagar',
            'image' => 'medical_camp_2023.jpg',
            'category' => 'Healthcare',
            'status' => 'journey',
            'people_helped' => 250,
            'volunteers' => 15,
            'budget' => 75000.00
        ],
        [
            'title' => 'Education Initiative Launch',
            'description' => 'Launch of our education support program',
            'event_date' => '2023-08-15',
            'location' => 'Mylapore',
            'image' => 'education_launch.jpg',
            'category' => 'Education',
            'status' => 'journey',
            'people_helped' => 100,
            'volunteers' => 10,
            'budget' => 50000.00
        ]
    ];

    foreach ($samplePosts as $post) {
        $sql = "INSERT INTO `events` 
                (`title`, `description`, `event_date`, `location`, `image`, `category`, `status`, `people_helped`, `volunteers`, `budget`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssiid",
            $post['title'],
            $post['description'],
            $post['event_date'],
            $post['location'],
            $post['image'],
            $post['category'],
            $post['status'],
            $post['people_helped'],
            $post['volunteers'],
            $post['budget']
        );
        
        if (!$stmt->execute()) {
            $errors[] = "Error inserting sample post '{$post['title']}': " . $stmt->error;
        }
        $stmt->close();
    }

    if (empty($errors)) {
        echo "Database update completed successfully!\n";
    } else {
        echo "Database update completed with errors:\n";
        foreach ($errors as $error) {
            echo "- $error\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
