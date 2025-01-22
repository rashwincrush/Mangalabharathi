<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ' . SITE_URL . '/admin/index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Debug logging
    error_log("Login attempt - Username: $username");
    
    try {
        if (login($username, $password)) {
            $redirect_url = $_SESSION['redirect_url'] ?? SITE_URL . '/admin/index.php';
            unset($_SESSION['redirect_url']);
            header("Location: $redirect_url");
            exit();
        } else {
            // More detailed error logging
            error_log("Login failed for username: $username");
            $error = "Invalid username or password";
        }
    } catch (Exception $e) {
        error_log("Login exception: " . $e->getMessage());
        $error = "An unexpected error occurred";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ff6b00;
            --secondary-color: #ff9100;
            --bg-gradient: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            --text-color: #333;
            --light-bg: #fff4e6;
        }
        
        body {
            background: var(--bg-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
        }
        
        .login-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(255,107,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            animation: fadeIn 0.5s ease-in-out;
            background-color: var(--light-bg);
        }
        
        .login-container h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }
        
        .form-control {
            border-radius: 25px;
            padding: 12px 20px;
            transition: all 0.3s ease;
            border-color: var(--secondary-color);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255,107,0,0.25);
        }
        
        .btn-login {
            background: var(--bg-gradient);
            border: none;
            border-radius: 25px;
            color: white;
            padding: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .btn-login:hover {
            transform: scale(1.05);
            background: var(--bg-gradient);
            color: white;
            box-shadow: 0 5px 15px rgba(255,107,0,0.4);
        }
        
        .input-group-text {
            background: transparent;
            border-right: none;
            color: var(--primary-color);
            border-color: var(--secondary-color);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert {
            border-radius: 10px;
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2><i class="bi bi-shield-lock text-warning"></i> Admin Login</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" novalidate>
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" 
                           class="form-control" 
                           id="username" 
                           name="username" 
                           placeholder="Username" 
                           required 
                           autocomplete="username">
                </div>
            </div>
            
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password" 
                           placeholder="Password" 
                           required 
                           autocomplete="current-password">
                </div>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Optional: Add client-side validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>
