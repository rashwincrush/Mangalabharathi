<?php
/**
 * Main configuration file
 * Sets up essential configurations, database connections, and security settings
 */

// Prevent direct script access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    die('Direct access not permitted');
}

// Ensure no output before session start
if (ob_get_level() > 0) {
    ob_clean();
}

// Security constants
defined('CSRF_TOKEN_NAME') || define('CSRF_TOKEN_NAME', 'CSRF_TOKEN');

// Session configuration constants
defined('SESSION_LIFETIME') || define('SESSION_LIFETIME', 7200); // 2 hours

// Ensure session is started only once
if (!function_exists('safe_session_start')) {
    function safe_session_start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure session parameters
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            // Start session
            session_start();
        }
    }
}

// Call safe session start
safe_session_start();

// Prevent multiple inclusions and function redeclarations
if (!defined('CONFIG_LOADED')) {
    define('CONFIG_LOADED', true);

    // Define constants only if they don't exist
    !defined('SITE_NAME') && define('SITE_NAME', 'Managalabhrathi Trust');
    !defined('SITE_URL') && define('SITE_URL', 'http://localhost:9090');
    !defined('ADMIN_URL') && define('ADMIN_URL', SITE_URL . '/admin');
    !defined('SITE_VERSION') && define('SITE_VERSION', '1.0.0');
    !defined('ADMIN_EMAIL') && define('ADMIN_EMAIL', 'admin@managalabhrathi.org');

    // Environment settings
    !defined('ENVIRONMENT') && define('ENVIRONMENT', 'development'); // 'development' or 'production'
    !defined('DEBUG_MODE') && define('DEBUG_MODE', ENVIRONMENT === 'development');

    // Database configuration with enhanced error handling
    !defined('DB_HOST') && define('DB_HOST', 'localhost');
    !defined('DB_USER') && define('DB_USER', 'root');
    !defined('DB_PASS') && define('DB_PASS', '');  // Empty password for local development
    !defined('DB_NAME') && define('DB_NAME', 'managalabhrathi');
    !defined('DB_PORT') && define('DB_PORT', '3306');
    !defined('DB_CHARSET') && define('DB_CHARSET', 'utf8mb4');

    // Security settings
    !defined('HASH_ALGO') && define('HASH_ALGO', PASSWORD_ARGON2ID);
    !defined('MAX_LOGIN_ATTEMPTS') && define('MAX_LOGIN_ATTEMPTS', 5);
    !defined('LOGIN_TIMEOUT') && define('LOGIN_TIMEOUT', 900); // 15 minutes

    // Ensure CSRF token is always set
    !defined('CSRF_TOKEN_NAME') && define('CSRF_TOKEN_NAME', 'csrf_token');

    // File upload settings
    !defined('UPLOAD_MAX_SIZE') && define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
    !defined('ALLOWED_FILE_TYPES') && define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);
    !defined('UPLOAD_PATH') && define('UPLOAD_PATH', __DIR__ . '/../uploads');

    // Enhanced error logging
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
    if (!is_dir(dirname(ini_get('error_log')))) {
        mkdir(dirname(ini_get('error_log')), 0755, true);
    }

    // Error reporting
    error_reporting(DEBUG_MODE ? E_ALL : E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', DEBUG_MODE ? 1 : 0);
    ini_set('log_errors', 1);

    // Set default timezone
    date_default_timezone_set('Asia/Kolkata');

    // Include database connection
    require_once __DIR__ . '/db.php';

    // Security headers
    header_remove('X-Powered-By');
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

// Utility functions
if (!function_exists('get_base_url')) {
    function get_base_url() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        return "$protocol://$host$uri";
    }
}

if (!function_exists('sanitize_input')) {
    function sanitize_input($input) {
        if (is_array($input)) {
            return array_map('sanitize_input', $input);
        }
        
        // Trim whitespace
        $input = trim($input);
        
        // Remove backslashes
        $input = stripslashes($input);
        
        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
}

// CSRF Token Generation
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
}

// CSRF Token Verification
if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(?string $token) {
        if (!$token || !isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
}

// Event Logging
if (!function_exists('log_event')) {
    function log_event(string $message, string $level = 'INFO') {
        $log_dir = __DIR__ . '/../logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_file = $log_dir . '/event.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        error_log($log_entry, 3, $log_file);
    }
}

// Session security
if (!function_exists('regenerate_session')) {
    function regenerate_session() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
        }
    }
}

// Call session security function
regenerate_session();

// Passive session configuration
if (!function_exists('passive_session_security')) {
    function passive_session_security() {
        // Check session inactivity
        $max_lifetime = SESSION_LIFETIME;
        
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $max_lifetime)) {
            // Last request was more than 2 hours ago
            session_unset();     // Unset $_SESSION variable for this page
            session_destroy();   // Destroy session data
            
            // Redirect to login page
            header('Location: ' . SITE_URL . '/admin/login.php');
            exit();
        }
        
        // Update last activity time stamp
        $_SESSION['LAST_ACTIVITY'] = time();
    }
}

// Register shutdown function to apply security settings
register_shutdown_function('passive_session_security');
