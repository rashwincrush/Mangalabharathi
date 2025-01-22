<?php
// System Recovery and Validation Utilities

function validate_database_connection($config) {
    try {
        $conn = new PDO(
            "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']}",
            $config['DB_USER'], 
            $config['DB_PASS']
        );
        
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Perform a simple query to test connection
        $conn->query("SELECT 1");
        
        return true;
    } catch (PDOException $e) {
        error_log("Database Validation Failed: " . $e->getMessage());
        return false;
    }
}

function regenerate_system_tokens() {
    // Regenerate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Optional: Regenerate other security tokens
    $_SESSION['session_token'] = bin2hex(random_bytes(32));
    
    error_log("System Tokens Regenerated: " . date('Y-m-d H:i:s'));
}

function validate_critical_files() {
    $critical_files = [
        '/includes/config.php',
        '/assets/css/variables.css',
        '/includes/db.php'
    ];
    
    $missing_files = [];
    
    foreach ($critical_files as $file) {
        $full_path = __DIR__ . '/../' . $file;
        if (!file_exists($full_path)) {
            $missing_files[] = $file;
            error_log("Missing Critical File: $file");
        }
    }
    
    return empty($missing_files);
}

function perform_system_recovery() {
    // Load configuration
    require_once 'config.php';
    
    // Validate database connection
    if (!validate_database_connection($CONFIG)) {
        die("Database connection could not be established.");
    }
    
    // Validate critical files
    if (!validate_critical_files()) {
        die("Some critical system files are missing.");
    }
    
    // Regenerate security tokens
    regenerate_system_tokens();
    
    // Log successful recovery
    error_log("System Recovery Completed Successfully: " . date('Y-m-d H:i:s'));
    
    return true;
}

// Automatic recovery on include
perform_system_recovery();
