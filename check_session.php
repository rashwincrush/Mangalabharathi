<?php
// Session Configuration Check
session_start();

echo "Session Configuration:\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Cookie Params:\n";
print_r(session_get_cookie_params());

// Test session storage
$_SESSION['test_key'] = 'test_value';
session_write_close();

// Verify session persistence
session_start();
echo "Session Test Value: " . ($_SESSION['test_key'] ?? 'Not Found') . "\n";
?>
