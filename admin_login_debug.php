<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Database connection
$conn = Database::getInstance()->getConnection();

// Function to reset admin password
function resetAdminPassword($conn) {
    $username = 'admin';
    $new_password = 'MB2025@Hostinger!';
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // First, check existing admin users
    $check_stmt = $conn->prepare("SELECT * FROM users WHERE role = 'admin'");
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    echo "Existing Admin Users:\n";
    while ($user = $result->fetch_assoc()) {
        print_r($user);
    }

    // Update or Insert admin user
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) 
                            VALUES (?, ?, 'admin@mangalabharathitrust.in', 'admin') 
                            ON DUPLICATE KEY UPDATE 
                            username = ?, 
                            password = ?");
    $stmt->bind_param("ssss", $username, $hashed_password, $username, $hashed_password);

    if ($stmt->execute()) {
        echo "Admin user successfully updated/created.\n";
        echo "New Credentials:\n";
        echo "Username: $username\n";
        echo "Password: $new_password\n";
    } else {
        echo "Error updating admin user: " . $stmt->error . "\n";
    }

    $stmt->close();
}

// Verify database connection
try {
    echo "Database Connection Status: Connected\n";
    echo "Database: " . $conn->query("SELECT DATABASE()")->fetch_array()[0] . "\n";
    
    // Reset admin password
    resetAdminPassword($conn);
} catch (Exception $e) {
    echo "Database Connection Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
