<?php
require_once '/Users/ashwin/CascadeProjects/managalabhrathi-trust/includes/config.php';
require_once '/Users/ashwin/CascadeProjects/managalabhrathi-trust/includes/db.php';

$admin_username = 'admin';
$admin_password = password_hash('ManaGalabhrathi2025!', PASSWORD_ARGON2ID);
$email = 'mangalabharathitrust@gmail.com';
$role = 'admin';

try {
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $admin_username, $admin_password, $email, $role);

    if ($stmt->execute()) {
        echo "Admin user created successfully!";
    } else {
        echo "Error creating admin user: " . $stmt->error;
    }
    $stmt->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
