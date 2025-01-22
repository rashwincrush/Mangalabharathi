-- Create event_impact_metrics table
CREATE TABLE IF NOT EXISTS event_impact_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    people_helped INT DEFAULT 0,
    volunteers INT DEFAULT 0,
    impact_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_impact_metrics_event_id 
    FOREIGN KEY (event_id) 
    REFERENCES events(id) 
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- Create event_media table
CREATE TABLE IF NOT EXISTS event_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    media_type ENUM('image', 'video') NOT NULL,
    media_url VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_media_event_id 
    FOREIGN KEY (event_id) 
    REFERENCES events(id) 
    ON DELETE CASCADE
) ENGINE=InnoDB;
