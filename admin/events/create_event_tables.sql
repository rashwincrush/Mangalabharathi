-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Events Table
CREATE TABLE IF NOT EXISTS events (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    location VARCHAR(255),
    category_id INT,
    status ENUM('draft', 'upcoming', 'past', 'archived') DEFAULT 'draft',
    expected_beneficiaries INT,
    volunteers_required INT,
    actual_volunteers INT,
    people_helped INT,
    impact_description TEXT,
    partner VARCHAR(255),
    donation_link VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Event Media Table
CREATE TABLE IF NOT EXISTS event_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id VARCHAR(36),
    media_type ENUM('image', 'video', 'youtube') NOT NULL,
    file_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Insert some default categories if not exists
INSERT IGNORE INTO categories (category) VALUES 
('Education'), 
('Healthcare'), 
('Community Development'), 
('Environmental'), 
('Skill Training');
