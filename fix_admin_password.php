<?php
// Fix admin user password
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_host = 'localhost';
$db_port = 3306;
$db_user = 'root';
$db_pass = 'root';
$db_name = 'managalabhrathi';

try {
    // Create connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Hash the password
    $new_password = password_hash('admin123', PASSWORD_DEFAULT);

    // Update user password
    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $update_stmt->bind_param("s", $new_password);

    if ($update_stmt->execute()) {
        echo "Admin password successfully updated with a secure hash.<br>";
    } else {
        echo "Error updating password: " . $update_stmt->error . "<br>";
    }

    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
