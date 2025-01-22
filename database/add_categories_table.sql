-- Add Categories Table
USE managalabhrathi;

-- Create Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert Default Categories
INSERT IGNORE INTO categories (category, description) VALUES
('Education', 'Events and initiatives focused on learning and skill development'),
('Healthcare', 'Medical camps, health awareness, and support programs'),
('Community Development', 'Projects that improve local community infrastructure and social welfare'),
('Sports', 'Athletic programs and support for youth sports'),
('Environmental Conservation', 'Initiatives to protect and preserve the environment'),
('Nutrition', 'Food security and nutrition support programs'),
('Skill Training', 'Vocational and professional skill development'),
('Women Empowerment', 'Programs supporting and empowering women'),
('Child Welfare', 'Initiatives focused on child health, education, and protection');

-- Update Events Table to use Category ID
ALTER TABLE events 
ADD COLUMN category_id INT NULL,
ADD CONSTRAINT fk_event_category 
FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

-- Migrate existing category data
UPDATE events e
JOIN categories c ON e.category = c.category
SET e.category_id = c.id;

-- Optional: Drop the old category column if no longer needed
-- ALTER TABLE events DROP COLUMN category;
