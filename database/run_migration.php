<?php
// Database Migration Script
require_once '../includes/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if categories table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'categories'");
    if ($check_table->rowCount() == 0) {
        // Create Categories Table
        $conn->exec("
            CREATE TABLE categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category VARCHAR(50) NOT NULL UNIQUE,
                description TEXT NULL,
                display_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        ");
        echo "Categories table created.\n";
    }

    // Insert default categories if not exists
    $check_categories = $conn->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($check_categories == 0) {
        $conn->exec("
            INSERT INTO categories (category, description) VALUES
            ('Education', 'Events and initiatives focused on learning and skill development'),
            ('Healthcare', 'Medical camps, health awareness, and support programs'),
            ('Community Development', 'Projects that improve local community infrastructure and social welfare'),
            ('Sports', 'Athletic programs and support for youth sports'),
            ('Environmental Conservation', 'Initiatives to protect and preserve the environment'),
            ('Nutrition', 'Food security and nutrition support programs'),
            ('Skill Training', 'Vocational and professional skill development'),
            ('Women Empowerment', 'Programs supporting and empowering women'),
            ('Child Welfare', 'Initiatives focused on child health, education, and protection')
        ");
        echo "Default categories inserted.\n";
    }

    // Check if category_id column exists in events table
    $column_check = $conn->query("SHOW COLUMNS FROM events LIKE 'category_id'")->rowCount();
    if ($column_check == 0) {
        // Add category_id column with foreign key
        $conn->exec("
            ALTER TABLE events 
            ADD COLUMN category_id INT NULL,
            ADD CONSTRAINT fk_event_category 
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        ");
        echo "Added category_id column to events table.\n";
    }

    // Migrate existing category data
    $conn->exec("
        UPDATE events e
        JOIN categories c ON e.category = c.category
        SET e.category_id = c.id
        WHERE e.category_id IS NULL
    ");
    echo "Migrated existing category data.\n";

} catch (PDOException $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    error_log("Database Migration Failed: " . $e->getMessage());
}
