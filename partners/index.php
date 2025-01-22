<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Check login
checkLogin();

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

// Get all partners ordered by display_order
$sql = "SELECT * FROM partners ORDER BY display_order ASC, name ASC";
$result = $conn->query($sql);

// Handle delete request
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get logo URL before deleting
    $logo_query = "SELECT logo_url FROM partners WHERE id = ?";
    $stmt = $conn->prepare($logo_query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $logo_result = $stmt->get_result();
    
    if ($logo_data = $logo_result->fetch_assoc()) {
        if ($logo_data['logo_url']) {
            $logo_path = dirname(dirname(__DIR__)) . '/' . $logo_data['logo_url'];
            if (file_exists($logo_path)) {
                unlink($logo_path);
            }
        }
    }
    
    // Delete the partner
    $delete_query = "DELETE FROM partners WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header('Location: index.php?deleted=1');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<!-- Rest of your HTML remains the same -->