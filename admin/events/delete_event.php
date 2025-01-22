<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Detailed error logging function
function logDetailedError($message, $context = []) {
    $logFile = dirname(__DIR__, 2) . '/logs/event_deletion_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    
    if (!empty($context)) {
        $logMessage .= "Context: " . print_r($context, true) . "\n";
    }
    
    // Ensure log directory exists
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Ensure clean JSON output function
function sendJsonResponse($data, $status_code = 200) {
    // Clear any previous output
    ob_clean();
    
    // Set response headers
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Set HTTP status code
    http_response_code($status_code);
    
    // Output JSON
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

session_start();

// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/EventManager.php';

// Response array
$response = [
    'success' => false,
    'message' => 'Unknown error occurred'
];

try {
    // Check if it's an AJAX request
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // Log incoming request details
    logDetailedError('Event Deletion Attempt', [
        'Session ID' => session_id(),
        'Admin ID' => $_SESSION['admin_id'] ?? 'Not set',
        'Request Method' => $_SERVER['REQUEST_METHOD'],
        'Event ID' => $_GET['id'] ?? 'Not provided',
        'Is AJAX' => $is_ajax ? 'Yes' : 'No',
        'Server Vars' => $_SERVER,
        'PHP Version' => PHP_VERSION,
        'Current Time' => date('Y-m-d H:i:s')
    ]);

    // Check if user is logged in and has admin privileges
    if (!isset($_SESSION['admin_id'])) {
        logDetailedError('Unauthorized Event Deletion Attempt');
        sendJsonResponse([
            'success' => false, 
            'message' => 'Unauthorized access'
        ], 401);
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logDetailedError('Invalid Method for Event Deletion');
        sendJsonResponse([
            'success' => false, 
            'message' => 'Method Not Allowed'
        ], 405);
    }

    // Validate event ID
    $event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($event_id === false || $event_id === null || $event_id <= 0) {
        logDetailedError('Invalid Event ID for Deletion', [
            'Provided Event ID' => $_GET['id'] ?? 'No ID'
        ]);
        sendJsonResponse([
            'success' => false, 
            'message' => 'Invalid event ID'
        ], 400);
    }

    // Create EventManager instance
    $eventManager = new EventManager();

    // Attempt to delete event
    try {
        $deletion_result = $eventManager->deleteEvent($event_id);

        if ($deletion_result) {
            // Log successful deletion
            logDetailedError('Event Deleted Successfully', [
                'Event ID' => $event_id
            ]);
            
            // Send success response
            sendJsonResponse([
                'success' => true, 
                'message' => 'Event deleted successfully'
            ]);
        } else {
            // Log failed deletion
            logDetailedError('Event Deletion Failed', [
                'Event ID' => $event_id,
                'Deletion Result' => $deletion_result
            ]);
            
            // Send failure response
            sendJsonResponse([
                'success' => false, 
                'message' => 'Event not found or already deleted'
            ], 404);
        }
    } catch (Exception $e) {
        // Log detailed error
        logDetailedError("Event Deletion Exception", [
            'Event ID' => $event_id,
            'Error Message' => $e->getMessage(),
            'Error Trace' => $e->getTraceAsString()
        ]);
        
        // Send error response with detailed message
        sendJsonResponse([
            'success' => false, 
            'message' => 'Error deleting event: ' . $e->getMessage()
        ], 500);
    }
} catch (Exception $e) {
    // Log the error
    logDetailedError("Unexpected Error in Event Deletion", [
        'Exception' => $e,
        'Trace' => $e->getTraceAsString()
    ]);
    
    // Send error response
    sendJsonResponse([
        'success' => false, 
        'message' => $e->getMessage()
    ], 500);
}
