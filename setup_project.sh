#!/bin/bash

# Exit on any error
set -e

# Ensure MySQL is running
if ! brew services list | grep mysql; then
    echo "Starting MySQL..."
    brew services start mysql
    sleep 5
fi

# Create database if not exists
mysql -u root -e "CREATE DATABASE IF NOT EXISTS managalabhrathi;"

# Create tables
mysql -u root managalabhrathi << EOF
CREATE TABLE IF NOT EXISTS team (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(255),
    bio TEXT,
    image_path VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date DATE,
    location VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    logo_path VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'editor') DEFAULT 'editor'
);

# Insert a default admin user if not exists
INSERT IGNORE INTO admin_users (username, password, email, role) VALUES 
('admin', '\$2y\$10\$rBxAZUVmRHDOjG0hnXz.UOqnWvGkTRNDQHnQ/qnmRHDOjG0hnXz.UO', 'admin@managalabhrathi.org', 'admin');
EOF

# Fix PHP configuration
sed -i '' 's/DB_PASS'\'' && define('\''DB_PASS'\'', '\''root'\'')/DB_PASS'\'' && define('\''DB_PASS'\'', '\''\'')/' /Users/ashwin/CascadeProjects/managalabhrathi-trust/includes/config.php

# Clear error logs
> /Users/ashwin/CascadeProjects/managalabhrathi-trust/logs/php_errors.log

echo "Project setup complete!"
