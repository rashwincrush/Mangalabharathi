-- Remove USE statement
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `event_date` DATETIME NOT NULL,
    `location` VARCHAR(255),
    `image` VARCHAR(255),
    `category` ENUM('education', 'health', 'community', 'environment') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert or update sample events
INSERT INTO `events` (`title`, `description`, `event_date`, `location`, `image`, `category`) VALUES 
('Community Health Camp', 'Free health checkup for local community', '2025-02-15 10:00:00', 'Community Center', 'health_camp.jpg', 'health'),
('Educational Workshop', 'Skill development for youth', '2025-03-20 14:00:00', 'Local School', 'education_workshop.jpg', 'education')
ON DUPLICATE KEY UPDATE 
title = VALUES(title), 
description = VALUES(description), 
event_date = VALUES(event_date), 
location = VALUES(location), 
image = VALUES(image), 
category = VALUES(category);
