<?php
// Disable error reporting to prevent HTML error output
error_reporting(0);
ini_set('display_errors', 0);

// Ensure clean output
ob_clean();

// Set JSON header for API-like response
header('Content-Type: application/json');

try {
    require_once dirname(dirname(__DIR__)) . '/includes/config.php';
    require_once dirname(dirname(__DIR__)) . '/includes/db.php';
    require_once '../includes/auth.php';

    // Check login
    checkLogin();

    // Extensive logging for debugging
    error_log("DELETE REQUEST DETAILS:");
    error_log("GET Parameters: " . print_r($_GET, true));
    error_log("POST Parameters: " . print_r($_POST, true));
    error_log("SERVER Variables: " . print_r($_SERVER, true));
    error_log("Session CSRF Token: " . ($_SESSION[CSRF_TOKEN_NAME] ?? 'Not Set'));

    // Ensure CSRF token exists
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        generate_csrf_token();
    }

    // CSRF Protection
    $csrf_token = $_GET[CSRF_TOKEN_NAME] ?? $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!$csrf_token) {
        // If no token provided, generate a new one and add it to the response
        $csrf_token = generate_csrf_token();
        error_log("Generated new CSRF token: $csrf_token");
    }

    if (!verify_csrf_token($csrf_token)) {
        // Debug logging
        error_log("CSRF Validation Failed. Received token: " . ($csrf_token ?? 'No token'));
        error_log("Stored Session Token: " . ($_SESSION[CSRF_TOKEN_NAME] ?? 'No session token'));
        
        throw new Exception('Security validation failed');
    }

    // Get database connection
    $conn = Database::getInstance()->getConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Function to check if table exists
    function tableExists($conn, $tableName) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->bind_param("s", $tableName);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    // Determine event ID from GET or POST
    $event_id = null;
    if (isset($_GET['id'])) {
        $event_id = (int)$_GET['id'];
    } elseif (isset($_POST['id'])) {
        $event_id = (int)$_POST['id'];
    } else {
        throw new Exception('No event ID provided');
    }

    // Begin transaction
    $conn->begin_transaction();

    // First, check if event exists
    $check_stmt = $conn->prepare("SELECT id FROM events WHERE id = ?");
    $check_stmt->bind_param("i", $event_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        throw new Exception('Event not found');
    }

    // Delete associated metrics (if table exists)
    if (tableExists($conn, 'event_impact_metrics')) {
        $delete_metrics_stmt = $conn->prepare("DELETE FROM event_impact_metrics WHERE event_id = ?");
        $delete_metrics_stmt->bind_param("i", $event_id);
        $delete_metrics_stmt->execute();
    }

    // Delete associated media (if table exists)
    if (tableExists($conn, 'event_media')) {
        $delete_media_stmt = $conn->prepare("DELETE FROM event_media WHERE event_id = ?");
        $delete_media_stmt->bind_param("i", $event_id);
        $delete_media_stmt->execute();
    }

    // Delete the event
    $delete_event_stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $delete_event_stmt->bind_param("i", $event_id);
    $delete_event_stmt->execute();

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Event deleted successfully'
    ]);
    exit();

} catch (Exception $e) {
    // Rollback transaction if it exists
    if (isset($conn) && method_exists($conn, 'rollback')) {
        $conn->rollback();
    }

    // Log the error
    error_log("Delete Event Error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'received_token' => $csrf_token ?? null,
            'session_token' => $_SESSION[CSRF_TOKEN_NAME] ?? null,
            'get_params' => $_GET,
            'post_params' => $_POST
        ]
    ]);
    exit();
}
