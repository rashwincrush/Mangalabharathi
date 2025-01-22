-- Comprehensive Database Initialization Script

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS managalabhrathi 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE managalabhrathi;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'editor', 'viewer') NOT NULL DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    profile_image VARCHAR(255) NULL,
    reset_token VARCHAR(100) NULL,
    reset_token_expiry TIMESTAMP NULL
) ENGINE=InnoDB;

-- Events Table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NOT NULL,
    image VARCHAR(255) NULL,
    category VARCHAR(50) NOT NULL,
    status ENUM('upcoming', 'past', 'cancelled') NOT NULL DEFAULT 'upcoming',
    location VARCHAR(255) NULL,
    people_helped INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Team Members Table
CREATE TABLE IF NOT EXISTS team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(100) NOT NULL,
    bio TEXT NULL,
    image VARCHAR(255) NULL,
    email VARCHAR(100) NULL,
    linkedin_url VARCHAR(255) NULL,
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Donations Table
CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_name VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    donation_date DATE NOT NULL,
    purpose VARCHAR(255) NULL,
    anonymous BOOLEAN DEFAULT FALSE,
    receipt_number VARCHAR(50) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Logs Table
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_level ENUM('INFO', 'WARNING', 'ERROR', 'CRITICAL') NOT NULL,
    message TEXT NOT NULL,
    context JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_id INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insert default admin user
INSERT IGNORE INTO users (
    username, 
    password, 
    email, 
    role
) VALUES (
    'admin', 
    '$2y$10$Qj/WjQ9E.Oa.Oa.Oa.Oa.Oa.Oa.Oa.Oa.Oa.Oa.Oa.Oa.Oa.Oa.Oa.Oa.Oa', -- Hashed password
    'mangalabharathitrust@gmail.com', 
    'admin'
);

-- Create indexes for performance
CREATE INDEX idx_events_date ON events(event_date);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_team_members_status ON team_members(status);
CREATE INDEX idx_donations_date ON donations(donation_date);
CREATE INDEX idx_system_logs_level ON system_logs(log_level);

-- Set MySQL mode for strict data validation
SET GLOBAL sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION';
