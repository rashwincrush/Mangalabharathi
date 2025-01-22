<?php
// Disable error reporting for this script
error_reporting(0);

// Set headers to prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Return a simple JSON response
header('Content-Type: application/json');

echo json_encode([
    'status' => 'ok',
    'timestamp' => time(),
    'message' => 'Connection test successful'
]);
exit;
?>
