<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Database connection
$conn = Database::getInstance()->getConnection();

// New admin credentials
$new_username = 'admin';
$new_password = 'MB2025@Hostinger!'; // Strong, unique password

// Hash the new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update admin user
$stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE role = 'admin'");
$stmt->bind_param("ss", $new_username, $hashed_password);

if ($stmt->execute()) {
    echo "Admin password successfully reset.<br>";
    echo "New Credentials:<br>";
    echo "Username: admin<br>";
    echo "Password: MB2025@Hostinger!<br>";
} else {
    echo "Error resetting password: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
