<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Diagnostic script for event deletion
echo "Event Deletion Diagnostic\n";
echo "----------------------\n";

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    echo "❌ Database connection FAILED\n";
    exit(1);
}

echo "✅ Database connection successful\n";

// Check events table
try {
    $result = $conn->query("SELECT COUNT(*) as event_count FROM events");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Total Events: " . $row['event_count'] . "\n";
    } else {
        echo "❌ Failed to query events table\n";
    }
} catch (Exception $e) {
    echo "❌ Query failed: " . $e->getMessage() . "\n";
}

// Test delete functionality with a sample event
try {
    // First, insert a test event
    $insert_stmt = $conn->prepare("INSERT INTO events (title, event_date, description) VALUES (?, CURDATE(), 'Test Event for Deletion')");
    $test_title = "Test Deletion Event " . date('Y-m-d H:i:s');
    $insert_stmt->bind_param("s", $test_title);
    
    if (!$insert_stmt->execute()) {
        echo "❌ Failed to insert test event\n";
        exit(1);
    }
    
    // Get the ID of the inserted event
    $test_event_id = $conn->insert_id;
    
    // Now try to delete the event
    $delete_stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $delete_stmt->bind_param("i", $test_event_id);
    
    if ($delete_stmt->execute()) {
        echo "✅ Successfully deleted test event\n";
    } else {
        echo "❌ Failed to delete test event\n";
    }
    
} catch (Exception $e) {
    echo "❌ Deletion test failed: " . $e->getMessage() . "\n";
}

$conn->close();
?>
