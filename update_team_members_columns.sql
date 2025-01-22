-- Rename name column to full_name
ALTER TABLE team_members 
CHANGE COLUMN name full_name VARCHAR(255) NOT NULL,
ADD COLUMN linkedin_url VARCHAR(500) DEFAULT NULL,
ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active',
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Update existing records
UPDATE team_members 
SET linkedin_url = CONCAT('https://linkedin.com/in/', LOWER(REPLACE(full_name, ' ', '-'))),
    status = 'active',
    display_order = id;
