<?php
require_once 'includes/config.php';

// Test database connection
echo "Database Connection Test:\n";
if ($conn === null) {
    echo "❌ Database connection failed\n";
} else {
    echo "✅ Database connection successful\n";
}

// Test admin user
$stmt = $conn->prepare("SELECT * FROM users WHERE username = 'admin'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin_user = $result->fetch_assoc();
    echo "\nAdmin User Details:\n";
    echo "Username: " . $admin_user['username'] . "\n";
    echo "Email: " . $admin_user['email'] . "\n";
    echo "Role: " . $admin_user['role'] . "\n";
} else {
    echo "❌ No admin user found\n";
}

// Test password verification
$test_password = 'admin123';
if (password_verify($test_password, $admin_user['password'])) {
    echo "\n✅ Password verification successful\n";
} else {
    echo "\n❌ Password verification failed\n";
}
?>
