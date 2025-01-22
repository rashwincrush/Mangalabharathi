-- Database Setup for Managalabhrathi Trust

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS managalabhrathi 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Use the database
USE managalabhrathi;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    role ENUM('admin', 'editor', 'viewer') DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Create events table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE,
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create donations table
CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_name VARCHAR(100),
    amount DECIMAL(10, 2) NOT NULL,
    donation_date DATE,
    purpose VARCHAR(255),
    anonymous BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create team_members table
CREATE TABLE IF NOT EXISTS team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(100),
    bio TEXT,
    image_url VARCHAR(255),
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (CHANGE PASSWORD IN PRODUCTION!)
INSERT IGNORE INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$vI8OGMzQ1qlsX0LtWgNOJOGqe9VXdqtTmsLyU/Qz7kRvmjhKqvqhC', 'admin@managalabhrathi.org', 'admin');

-- Create application user with secure password
CREATE USER IF NOT EXISTS 'managalabhrathi_app'@'localhost' IDENTIFIED BY 'secure_app_password';
GRANT ALL PRIVILEGES ON managalabhrathi.* TO 'managalabhrathi_app'@'localhost';
FLUSH PRIVILEGES;
