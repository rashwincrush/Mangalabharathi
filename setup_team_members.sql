-- Create team_members table
CREATE TABLE team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(255) NOT NULL,
    bio TEXT,
    image_url VARCHAR(500),
    email VARCHAR(255),
    linkedin_url VARCHAR(500),
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert some sample team members
INSERT INTO team_members (name, role, bio, image_url, email, linkedin_url, display_order, status) VALUES 
('John Doe', 'Founder & CEO', 'Passionate about social change and community development.', '/uploads/team/john-doe.jpg', 'john.doe@example.com', 'https://linkedin.com/in/johndoe', 1, 'active'),
('Jane Smith', 'Program Director', 'Dedicated to empowering local communities.', '/uploads/team/jane-smith.jpg', 'jane.smith@example.com', 'https://linkedin.com/in/janesmith', 2, 'active');
