-- Update team_members table
ALTER TABLE team_members 
MODIFY COLUMN position VARCHAR(100),
ADD COLUMN display_order INT DEFAULT 0,
ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active',
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN linkedin_url VARCHAR(500);

-- Insert some sample team members if not exists
INSERT IGNORE INTO team_members (name, position, bio, image_url, email, linkedin_url, display_order, status) VALUES 
('John Doe', 'Founder & CEO', 'Passionate about social change and community development.', '/uploads/team/john-doe.jpg', 'john.doe@example.com', 'https://linkedin.com/in/johndoe', 1, 'active'),
('Jane Smith', 'Program Director', 'Dedicated to empowering local communities.', '/uploads/team/jane-smith.jpg', 'jane.smith@example.com', 'https://linkedin.com/in/janesmith', 2, 'active');
