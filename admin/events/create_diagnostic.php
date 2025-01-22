<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/EventManager.php';

// Diagnostic script for event creation
echo "Event Creation Diagnostic\n";
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

// Check categories table
try {
    $categories_result = $conn->query("SELECT COUNT(*) as category_count FROM categories");
    if ($categories_result) {
        $row = $categories_result->fetch_assoc();
        echo "Total Categories: " . $row['category_count'] . "\n";
    } else {
        echo "❌ Failed to query categories table\n";
    }
} catch (Exception $e) {
    echo "❌ Categories query failed: " . $e->getMessage() . "\n";
}

// Test event creation functionality
try {
    // Initialize EventManager
    $eventManager = new EventManager($conn);
    
    // Prepare test event data
    $test_event_data = [
        'title' => 'Diagnostic Test Event ' . date('Y-m-d H:i:s'),
        'description' => 'This is a test event created by the diagnostic script',
        'event_date' => date('Y-m-d', strtotime('+1 month')),
        'location' => 'Diagnostic Test Location',
        'category' => 'Test',
        'status' => 'draft',
        'people_helped' => 0,
        'volunteers' => 0,
        'budget' => 0,
        'impact_description' => 'Diagnostic test impact description'
    ];
    
    // Attempt to create the event
    $event_id = $eventManager->createEvent($test_event_data);
    
    if ($event_id) {
        echo "✅ Successfully created test event (ID: $event_id)\n";
        
        // Verify the event was created
        $verify_query = $conn->prepare("SELECT * FROM events WHERE id = ?");
        $verify_query->bind_param("i", $event_id);
        $verify_query->execute();
        $result = $verify_query->get_result();
        
        if ($result->num_rows > 0) {
            echo "✅ Test event verified in the database\n";
        } else {
            echo "❌ Failed to verify test event\n";
        }
    } else {
        echo "❌ Failed to create test event\n";
    }
    
} catch (Exception $e) {
    echo "❌ Event creation test failed: " . $e->getMessage() . "\n";
}

$conn->close();
?>
