<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/EventManager.php';
require_once '../includes/auth.php';

// Logging function for detailed tracking
function log_bulk_delete_action($message, $level = 'INFO') {
    error_log("[BULK DELETE] " . $message);
}

// CSRF Protection
if (!isset($_POST[CSRF_TOKEN_NAME]) || !verify_csrf_token($_POST[CSRF_TOKEN_NAME])) {
    log_bulk_delete_action("CSRF token validation failed", 'ERROR');
    $_SESSION['error_message'] = "Security validation failed. Please try again.";
    header('Location: /admin/events/index.php');
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    log_bulk_delete_action("Unauthorized access attempt", 'WARNING');
    $_SESSION['error_message'] = "You must be logged in to perform this action.";
    header('Location: /admin/login.php');
    exit();
}

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['selected_events'])) {
    log_bulk_delete_action("Invalid request - no events selected", 'WARNING');
    $_SESSION['error_message'] = "Invalid request. No events selected for deletion.";
    header('Location: /admin/events/index.php');
    exit();
}

// Sanitize and validate event IDs
$selected_events = array_map('intval', $_POST['selected_events']);
$selected_events = array_filter($selected_events, function($id) { return $id > 0; });

if (empty($selected_events)) {
    log_bulk_delete_action("No valid events selected", 'WARNING');
    $_SESSION['error_message'] = "No valid events selected for deletion.";
    header('Location: /admin/events/index.php');
    exit();
}

try {
    // Get database connection
    $conn = Database::getInstance()->getConnection();
    
    // Create EventManager instance
    $eventManager = new EventManager($conn);
    
    // Begin transaction
    $conn->begin_transaction();
    
    // Track successful and failed deletions
    $deleted_events = [];
    $failed_events = [];
    
    // Delete each selected event
    foreach ($selected_events as $event_id) {
        try {
            // First, delete associated media
            $media_delete_query = "DELETE FROM event_media WHERE event_id = ?";
            $media_stmt = $conn->prepare($media_delete_query);
            $media_stmt->bind_param('i', $event_id);
            $media_stmt->execute();
            
            // Delete impact metrics
            $metrics_delete_query = "DELETE FROM event_impact_metrics WHERE event_id = ?";
            $metrics_stmt = $conn->prepare($metrics_delete_query);
            $metrics_stmt->bind_param('i', $event_id);
            $metrics_stmt->execute();
            
            // Then delete event
            if ($eventManager->deleteEvent($event_id)) {
                $deleted_events[] = $event_id;
                log_bulk_delete_action("Successfully deleted event ID: $event_id");
            } else {
                $failed_events[] = $event_id;
                log_bulk_delete_action("Failed to delete event ID: $event_id", 'ERROR');
            }
        } catch (Exception $e) {
            $failed_events[] = $event_id;
            log_bulk_delete_action("Error deleting event $event_id: " . $e->getMessage(), 'ERROR');
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Prepare success/error messages
    if (!empty($deleted_events)) {
        $_SESSION['success_message'] = count($deleted_events) . " event(s) successfully deleted.";
        log_bulk_delete_action(count($deleted_events) . " events deleted successfully");
    }
    
    if (!empty($failed_events)) {
        $_SESSION['error_message'] = "Failed to delete " . count($failed_events) . " event(s). Event IDs: " . implode(', ', $failed_events);
        log_bulk_delete_action("Failed to delete " . count($failed_events) . " events", 'WARNING');
    }
    
} catch (Exception $e) {
    // Rollback transaction
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollback();
    }
    
    // Log and set error message
    log_bulk_delete_action("Bulk delete error: " . $e->getMessage(), 'ERROR');
    $_SESSION['error_message'] = "An error occurred while deleting events: " . $e->getMessage();
}

// Ensure correct redirect
header('Location: /admin/events/index.php');
exit();
?>
