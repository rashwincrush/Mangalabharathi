<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Create tables with error handling
    $tables = [
        // Users table
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            role ENUM('admin', 'editor', 'viewer') NOT NULL DEFAULT 'viewer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'
        ) ENGINE=InnoDB",

        // Categories table
        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(50) NOT NULL UNIQUE,
            description TEXT NULL,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        // Events table
        "CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            event_date DATE NOT NULL,
            image VARCHAR(255) NULL,
            category_id INT NULL,
            status ENUM('upcoming', 'past', 'cancelled') NOT NULL DEFAULT 'upcoming',
            location VARCHAR(255) NULL,
            people_helped INT DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

        // Team Members table
        "CREATE TABLE IF NOT EXISTS team_members (
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
        ) ENGINE=InnoDB",

        // System Logs table
        "CREATE TABLE IF NOT EXISTS system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            log_level ENUM('INFO', 'WARNING', 'ERROR', 'CRITICAL') NOT NULL,
            message TEXT NOT NULL,
            context JSON NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            user_id INT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB"
    ];

    // Execute each table creation query
    foreach ($tables as $sql) {
        try {
            $conn->exec($sql);
            echo "Table created successfully\n";
        } catch (PDOException $e) {
            echo "Error creating table: " . $e->getMessage() . "\n";
        }
    }

    // Prepare default admin user
    $admin_check = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $admin_check->execute(['username' => 'admin']);
    
    if (!$admin_check->fetch()) {
        // Generate a secure default password
        $default_password = password_hash('MangalabhrathiAdmin2025!', PASSWORD_DEFAULT);
        
        // Insert default admin user
        $admin_insert = $conn->prepare("
            INSERT INTO users (username, password, email, role, status) 
            VALUES (:username, :password, :email, 'admin', 'active')
        ");
        
        $admin_insert->execute([
            'username' => 'admin',
            'password' => $default_password,
            'email' => 'admin@mangalabhrathi.org'
        ]);
        
        echo "Default admin user created\n";
        echo "Username: admin\n";
        echo "Password: MangalabhrathiAdmin2025!\n";
    } else {
        echo "Admin user already exists\n";
    }

    // Insert default categories
    $default_categories = [
        ['Education', 'Educational initiatives and programs'],
        ['Healthcare', 'Medical camps and health awareness programs'],
        ['Community Development', 'Projects for community welfare'],
        ['Sports', 'Sports activities and training'],
        ['Environmental', 'Environmental conservation efforts'],
        ['Cultural', 'Cultural events and celebrations']
    ];

    $category_insert = $conn->prepare("INSERT IGNORE INTO categories (category, description) VALUES (:category, :description)");
    foreach ($default_categories as $category) {
        try {
            $category_insert->execute([
                'category' => $category[0], 
                'description' => $category[1]
            ]);
        } catch (PDOException $e) {
            echo "Error inserting category {$category[0]}: " . $e->getMessage() . "\n";
        }
    }

    echo "Database initialization completed successfully\n";

} catch (PDOException $e) {
    echo "Database initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}
