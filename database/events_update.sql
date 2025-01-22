-- Modify events table to support both upcoming events and journey posts
ALTER TABLE `events`
    MODIFY COLUMN `status` ENUM('upcoming', 'past', 'journey') NOT NULL DEFAULT 'upcoming',
    ADD COLUMN `location` varchar(255) AFTER `event_date`,
    ADD COLUMN `people_helped` int DEFAULT NULL AFTER `category`,
    ADD COLUMN `volunteers` int DEFAULT NULL AFTER `people_helped`,
    ADD COLUMN `budget` decimal(10,2) DEFAULT NULL AFTER `volunteers`;

-- Create event_media table for multiple images and documents
CREATE TABLE IF NOT EXISTS `event_media` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `event_id` int(11) NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `media_type` ENUM('image', 'document') NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample journey posts
INSERT INTO `events` (`title`, `description`, `event_date`, `location`, `image`, `category`, `status`, `people_helped`, `volunteers`, `budget`) VALUES
('First Medical Camp', 'Our first major medical camp serving the community', '2023-06-10', 'T.Nagar', 'medical_camp_2023.jpg', 'Healthcare', 'journey', 250, 15, 75000.00),
('Education Initiative Launch', 'Launch of our education support program', '2023-08-15', 'Mylapore', 'education_launch.jpg', 'Education', 'journey', 100, 10, 50000.00);
