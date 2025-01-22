<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';

// Detailed database connection diagnostic
echo "Attempting to establish database connection...\n";

// Get database connection
$conn = get_db_connection();

if (!$conn) {
    echo "❌ Database connection FAILED\n";
    echo "Possible reasons:\n";
    echo "- Incorrect database credentials\n";
    echo "- Database server not running\n";
    echo "- Network issues\n";
    
    // Additional diagnostic information
    echo "\nCurrent Configuration:\n";
    echo "Host: " . DB_HOST . "\n";
    echo "Port: " . DB_PORT . "\n";
    echo "User: " . DB_USER . "\n";
    echo "Database: " . DB_NAME . "\n";
    
    exit(1);
}

echo "✅ Database connection successful!\n";

// Test a simple query
try {
    $result = $conn->query("SELECT COUNT(*) as partner_count FROM partners");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Total Partners: " . $row['partner_count'] . "\n";
    } else {
        echo "❌ Failed to query partners table\n";
    }
} catch (Exception $e) {
    echo "❌ Query failed: " . $e->getMessage() . "\n";
}

$conn->close();
?>
