<?php
require_once 'includes/config.php';

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    die("Database connection failed.");
}

// SQL to add columns and modify table
$alter_queries = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL"
];

foreach ($alter_queries as $query) {
    if ($conn->query($query) === TRUE) {
        echo "Query executed successfully: $query\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}

// Optional: Update existing users to active if not set
$update_query = "UPDATE users SET status = 'active' WHERE status IS NULL";
if ($conn->query($update_query) === TRUE) {
    echo "Updated existing users to active status.\n";
}

$conn->close();
?>
