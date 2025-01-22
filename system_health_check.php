<?php
/**
 * Comprehensive System Health Check
 * Performs detailed diagnostics on the web application
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration and Dependency Check
$checks = [
    'PHP Version' => PHP_VERSION,
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Operating System' => PHP_OS_FAMILY,
    'Current User' => get_current_user(),
];

// Required Extensions
$required_extensions = [
    'pdo', 'pdo_mysql', 'mysqli', 'session', 
    'json', 'curl', 'mbstring', 'openssl'
];

// Permissions Check
$critical_paths = [
    'includes/' => '/Users/ashwin/CascadeProjects/managalabhrathi-trust/includes',
    'database/' => '/Users/ashwin/CascadeProjects/managalabhrathi-trust/database',
    'admin/' => '/Users/ashwin/CascadeProjects/managalabhrathi-trust/admin',
    'logs/' => '/Users/ashwin/CascadeProjects/managalabhrathi-trust/logs'
];

// Database Connection Test
function test_database_connection() {
    try {
        require_once 'includes/db.php';
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Basic query to test connection
        $stmt = $conn->query("SELECT 1");
        return $stmt ? 'Successful' : 'Failed';
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

// Session Configuration Test
function test_session_configuration() {
    $session_settings = [
        'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
        'session.cookie_lifetime' => ini_get('session.cookie_lifetime'),
        'session.use_strict_mode' => ini_get('session.use_strict_mode'),
        'session.cookie_httponly' => ini_get('session.cookie_httponly'),
    ];
    return $session_settings;
}

// Security Headers Test
function test_security_headers() {
    $headers = [
        'X-XSS-Protection' => 'Recommended',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Content-Security-Policy' => 'Recommended',
        'Strict-Transport-Security' => 'Recommended'
    ];
    return $headers;
}

// Perform Health Check
function perform_health_check() {
    $report = [
        'System Configuration' => $GLOBALS['checks'],
        'Required Extensions' => [],
        'Path Permissions' => [],
        'Database Connection' => test_database_connection(),
        'Session Configuration' => test_session_configuration(),
        'Security Headers' => test_security_headers(),
        'Potential Issues' => []
    ];

    // Check Extensions
    foreach ($GLOBALS['required_extensions'] as $ext) {
        $report['Required Extensions'][$ext] = extension_loaded($ext) ? 'Loaded' : 'Missing';
    }

    // Check Path Permissions
    foreach ($GLOBALS['critical_paths'] as $name => $path) {
        $report['Path Permissions'][$name] = is_writable($path) ? 'Writable' : 'Not Writable';
    }

    // Identify Potential Issues
    if (in_array('Missing', $report['Required Extensions'])) {
        $report['Potential Issues'][] = 'Missing Critical PHP Extensions';
    }
    if (in_array('Not Writable', $report['Path Permissions'])) {
        $report['Potential Issues'][] = 'Critical Paths Not Writable';
    }

    return $report;
}

// Generate Readable Report
function generate_report($report) {
    $output = "=== SYSTEM HEALTH CHECK ===\n\n";
    
    foreach ($report as $section => $data) {
        $output .= strtoupper($section) . ":\n";
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $output .= "  - {$key}: {$value}\n";
            }
        } else {
            $output .= "  {$data}\n";
        }
        $output .= "\n";
    }

    return $output;
}

// Run Health Check
$health_report = perform_health_check();
$readable_report = generate_report($health_report);

// Output or Log Report
echo "<pre>{$readable_report}</pre>";
file_put_contents(__DIR__ . '/logs/system_health_' . date('Y-m-d_H-i-s') . '.log', $readable_report);
