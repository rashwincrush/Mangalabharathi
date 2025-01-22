<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/EventManager.php';

header('Content-Type: application/json');

// Initialize
$conn = Database::getInstance()->getConnection();
$eventManager = new EventManager($conn);

// Get event ID from request
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($event_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid event ID']);
    exit;
}

// Fetch event details
$event = $eventManager->getEventDetails($event_id);

if ($event) {
    echo json_encode($event);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Event not found']);
}
exit;
