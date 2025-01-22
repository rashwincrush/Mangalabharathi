-- Create database
CREATE DATABASE IF NOT EXISTS `u274792269_MB`;

-- Use the database
USE `u274792269_MB`;

-- Create events table
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `event_date` DATE NOT NULL,
    `location` VARCHAR(255),
    `event_type` ENUM('upcoming', 'past') NOT NULL,
    `image_url` VARCHAR(255),
    `registration_link` VARCHAR(255)
);

-- Insert some sample events
INSERT INTO `events` (`title`, `description`, `event_date`, `location`, `event_type`, `image_url`, `registration_link`) VALUES
('Community Health Camp', 'Free medical checkup and health awareness', '2024-02-15', 'Community Center, Bangalore', 'upcoming', 'health_camp.jpg', 'https://example.com/register/health-camp'),
('Tree Plantation Drive', 'Annual environmental conservation event', '2024-03-10', 'City Park, Bangalore', 'upcoming', 'tree_plantation.jpg', 'https://example.com/register/tree-plantation'),
('Education Support Workshop', 'Scholarship distribution and career guidance', '2023-11-20', 'Trust Headquarters', 'past', 'education_workshop.jpg', NULL),
('Blood Donation Camp', 'Community blood donation initiative', '2023-10-05', 'City Hospital', 'past', 'blood_donation.jpg', NULL);
