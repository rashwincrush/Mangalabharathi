# Local Database Setup Guide

## Prerequisites
- MySQL installed (via Homebrew recommended)
- PHP with MySQLi extension

## Step 1: Start MySQL
```bash
brew services start mysql
```

## Step 2: Connect to MySQL
```bash
mysql -u root
```

## Step 3: Create Database and User
```sql
-- Create database
CREATE DATABASE u274792269_MB;

-- Create user (optional, but recommended)
CREATE USER 'mbuser'@'localhost' IDENTIFIED BY 'MB@2025local';
GRANT ALL PRIVILEGES ON u274792269_MB.* TO 'mbuser'@'localhost';
FLUSH PRIVILEGES;
```

## Step 4: Create Events Table
```sql
USE u274792269_MB;

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    location VARCHAR(255),
    event_type ENUM('upcoming', 'past') NOT NULL,
    image_url VARCHAR(255),
    registration_link VARCHAR(255)
);

-- Insert sample events
INSERT INTO events (title, description, event_date, location, event_type) VALUES
('Community Health Camp', 'Free medical checkup', '2024-02-15', 'Bangalore', 'upcoming'),
('Tree Plantation', 'Environmental initiative', '2023-11-20', 'City Park', 'past');
```

## Troubleshooting
- Ensure MySQL is running
- Check MySQL error logs
- Verify database and user permissions

## Update PHP Configuration
Modify `events.php` to use the new database credentials:
```php
$conn = new mysqli('localhost', 'mbuser', 'MB@2025local', 'u274792269_MB');
```
