<?php
require_once 'includes/config.php';

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    die("Database connection failed.");
}

// SQL to create partners table
$create_table_sql = "CREATE TABLE IF NOT EXISTS partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    logo_url VARCHAR(255),
    website_url VARCHAR(255),
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Execute table creation
if ($conn->query($create_table_sql) === TRUE) {
    echo "Partners table created successfully or already exists.\n";
    
    // Check if table is empty, insert sample data if needed
    $check_data = $conn->query("SELECT COUNT(*) as count FROM partners");
    $data_count = $check_data->fetch_assoc()['count'];
    
    if ($data_count == 0) {
        $sample_data_sql = "INSERT INTO partners (name, description, logo_url, website_url, display_order, status) VALUES 
        ('Sample Partner 1', 'A great organization', '/uploads/partners/logo1.png', 'https://example1.com', 1, 'active'),
        ('Sample Partner 2', 'Another amazing organization', '/uploads/partners/logo2.png', 'https://example2.com', 2, 'active')";
        
        if ($conn->query($sample_data_sql) === TRUE) {
            echo "Sample partner data inserted.\n";
        } else {
            echo "Error inserting sample data: " . $conn->error . "\n";
        }
    }
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>
