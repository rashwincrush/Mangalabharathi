<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../includes/auth.php';

// Check login
checkLogin();

// Get database connection
$conn = Database::getInstance()->getConnection();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['category'])) {
        throw new Exception('Category name is required');
    }
    
    $category = trim($data['category']);
    
    // Check if category already exists
    $check_stmt = $conn->prepare("SELECT id FROM categories WHERE category = ?");
    $check_stmt->bind_param('s', $category);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Category already exists'
        ]);
        exit;
    }
    
    // Insert new category
    $stmt = $conn->prepare("INSERT INTO categories (category) VALUES (?)");
    $stmt->bind_param('s', $category);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'id' => $stmt->insert_id,
            'message' => 'Category added successfully'
        ]);
    } else {
        throw new Exception('Failed to add category');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
