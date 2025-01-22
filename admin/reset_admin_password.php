<?php
require_once dirname(__DIR__) . '/includes/config.php';

// Get database connection
$conn = get_db_connection();

if (!$conn) {
    die("Database connection failed");
}

// New password to set
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Prepare and execute update statement
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->bind_param("s", $hashed_password);

if ($stmt->execute()) {
    echo "Admin password reset successful. New password is: $new_password\n";
    
    // Verify the update
    $verify_stmt = $conn->prepare("SELECT password FROM users WHERE username = 'admin'");
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify($new_password, $user['password'])) {
        echo "Password verification successful.\n";
    } else {
        echo "Password verification failed.\n";
    }
} else {
    echo "Error resetting password: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
