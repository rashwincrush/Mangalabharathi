<?php
require_once 'includes/config.php';
require_once 'admin/includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Example form with CSRF protection
function render_secure_form() {
    ?>
    <form method="POST" action="process.php">
        <!-- Other form fields -->
        <input type="text" name="username">
        <input type="password" name="password">
        
        <!-- CSRF Token Protection -->
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <button type="submit">Submit</button>
    </form>
    <?php
}

// Example processing script (process.php)
function process_form() {
    // Verify CSRF token before processing
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    
    // Process form data
    // ...
}
?>
