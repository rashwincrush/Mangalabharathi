<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

// Admin authentication check
function check_admin_auth() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
        header('Location: /admin/login.php');
        exit();
    }
}

// Get database connection for admin operations
function get_admin_db() {
    static $db = null;
    if ($db === null) {
        $db = Database::getInstance();
    }
    return $db->getConnection();
}

// Secure file upload function
function handle_file_upload($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    try {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Invalid parameters.');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('No file sent.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('Exceeded filesize limit.');
            default:
                throw new RuntimeException('Unknown errors.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_types)) {
            throw new RuntimeException('Invalid file format.');
        }

        $filename = sprintf('%s.%s',
            sha1_file($file['tmp_name']),
            $ext
        );

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (!move_uploaded_file(
            $file['tmp_name'],
            $target_dir . DIRECTORY_SEPARATOR . $filename
        )) {
            throw new RuntimeException('Failed to move uploaded file.');
        }

        return $filename;
    } catch (RuntimeException $e) {
        error_log("File upload error: " . $e->getMessage());
        return false;
    }
}

// Log admin actions
function log_admin_action($action, $details = []) {
    try {
        $conn = get_admin_db();
        $stmt = $conn->prepare("
            INSERT INTO system_logs (log_level, message, context, user_id) 
            VALUES ('INFO', ?, ?, ?)
        ");
        $context = json_encode($details);
        $user_id = $_SESSION['admin_id'] ?? null;
        $stmt->execute([$action, $context, $user_id]);
        return true;
    } catch (Exception $e) {
        error_log("Error logging admin action: " . $e->getMessage());
        return false;
    }
}

// Validate and sanitize input
function validate_admin_input($data, $required_fields = []) {
    $errors = [];
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        if (in_array($key, $required_fields) && empty($value)) {
            $errors[] = ucfirst($key) . " is required.";
        }
        $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
    
    return ['data' => $sanitized, 'errors' => $errors];
}

// Generate pagination
function generate_admin_pagination($total_items, $items_per_page, $current_page) {
    $total_pages = ceil($total_items / $items_per_page);
    $pagination = [];
    
    if ($total_pages > 1) {
        for ($i = 1; $i <= $total_pages; $i++) {
            $pagination[] = [
                'page' => $i,
                'current' => $i == $current_page,
                'url' => '?page=' . $i
            ];
        }
    }
    
    return $pagination;
}
