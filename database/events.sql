-- Create events table
CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `event_date` date NOT NULL,
  `image` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `status` enum('upcoming','past') NOT NULL DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample events
INSERT INTO `events` (`title`, `description`, `event_date`, `image`, `category`, `status`) VALUES
('Education Support Drive', 'Distribution of educational materials to underprivileged students', '2025-01-15', 'education.jpg', 'Education', 'upcoming'),
('Health Camp', 'Free medical checkup camp for the community', '2025-01-20', 'health.jpg', 'Healthcare', 'upcoming'),
('Food Distribution', 'Providing nutritious meals to those in need', '2025-01-25', 'food.jpg', 'Community', 'upcoming');
