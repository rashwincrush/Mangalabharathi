<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$DB_HOST = 'localhost';
$DB_PORT = '3306';
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'mangalabharathi';

try {
    echo "Starting database setup...<br>";
    
    // Create connection without database
    echo "Connecting to MySQL server...<br>";
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, '', $DB_PORT);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "Connected successfully to MySQL server.<br>";
    
    // Create database if not exists
    echo "Creating database if not exists...<br>";
    $sql = "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$conn->query($sql)) {
        throw new Exception("Error creating database: " . $conn->error);
    }
    echo "Database created/verified successfully.<br>";
    
    // Select the database
    echo "Selecting database...<br>";
    if (!$conn->select_db($DB_NAME)) {
        throw new Exception("Error selecting database: " . $conn->error);
    }
    echo "Database selected successfully.<br>";
    
    // Set SQL mode
    echo "Setting SQL mode...<br>";
    $conn->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    
    // Create users table and admin user
    echo "Creating users table...<br>";
    $users_table = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL,
        role ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($users_table)) {
        throw new Exception("Error creating users table: " . $conn->error);
    }
    echo "Users table created successfully.<br>";
    
    // Check if admin user exists
    echo "Checking for admin user...<br>";
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $admin_username = 'admin';
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "Creating admin user...<br>";
        // Create admin user
        $insert_stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')");
        $username = 'admin';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $email = 'admin@mangalabharathitrust.in';
        
        $insert_stmt->bind_param("sss", $username, $password, $email);
        
        if (!$insert_stmt->execute()) {
            throw new Exception("Error creating admin user: " . $insert_stmt->error);
        }
        $insert_stmt->close();
        echo "Admin user created successfully.<br>";
    } else {
        echo "Admin user already exists.<br>";
    }
    $stmt->close();
    
    // Include tables.php to create other tables
    echo "Creating other tables...<br>";
    require_once 'includes/tables.php';
    echo "All tables created successfully!<br>";
    
    echo "<br>Database setup completed successfully!<br>";
    echo "Admin credentials:<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    
} catch (Exception $e) {
    die("Setup failed: " . $e->getMessage() . "<br>Check error log for details.");
}
