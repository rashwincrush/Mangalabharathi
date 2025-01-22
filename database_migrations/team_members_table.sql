-- Create team_members table
CREATE TABLE IF NOT EXISTS team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    bio TEXT,
    photo_url VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add some initial team members
INSERT INTO team_members (name, position, bio, photo_url, display_order) VALUES 
(
    'Sample Team Member', 
    'Founder & Chairman', 
    'Passionate about community development and social welfare.', 
    '/assets/images/team/default_profile.jpg', 
    1
),
(
    'Another Team Member', 
    'Executive Director', 
    'Dedicated to creating positive change in society.', 
    '/assets/images/team/default_profile.jpg', 
    2
);
