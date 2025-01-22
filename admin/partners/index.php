<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Check login
checkLogin();

// Get database connection
try {
    $conn = Database::getInstance()->getConnection();
    
    // Verify database connection and table exists
    if ($conn instanceof PDO) {
        $table_check = $conn->query("SHOW TABLES LIKE 'partners'")->rowCount();
        $columns_check = $conn->query("SHOW COLUMNS FROM partners")->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($conn instanceof mysqli) {
        $table_check_result = $conn->query("SHOW TABLES LIKE 'partners'");
        $table_check = $table_check_result ? $table_check_result->num_rows : 0;
        
        $columns_check = [];
        $result = $conn->query("SHOW COLUMNS FROM partners");
        while ($row = $result->fetch_array()) {
            $columns_check[] = $row[0];
        }
    } else {
        throw new Exception("Unsupported database connection type");
    }
    
    if ($table_check === 0) {
        throw new Exception("Partners table does not exist in the database.");
    }
    
    // Verify table structure
    $required_columns = ['id', 'name', 'logo_url', 'status', 'description', 'display_order'];
    $missing_columns = array_diff($required_columns, $columns_check);
    
    if (!empty($missing_columns)) {
        throw new Exception("Missing columns in partners table: " . implode(', ', $missing_columns));
    }
    
    // Get all partners ordered by display_order
    $sql = "SELECT * FROM partners ORDER BY display_order ASC, name ASC";
    
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($conn instanceof mysqli) {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $partners = [];
        while ($row = $result->fetch_assoc()) {
            $partners[] = $row;
        }
        $stmt->close();
    } else {
        throw new Exception("Unsupported database connection type");
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Partners Database Error: " . $e->getMessage());
    
    // Display a user-friendly error message
    $error_message = $e->getMessage();
    $error_details = $e->getMessage();
    $partners = [];
}

// Handle delete request
if (isset($_GET['delete'])) {
    try {
        $id = (int)$_GET['delete'];
        
        // Get logo URL before deleting
        $logo_query = "SELECT logo_url FROM partners WHERE id = ?";
        
        if ($conn instanceof PDO) {
            $logo_stmt = $conn->prepare($logo_query);
            $logo_stmt->bindParam(1, $id, PDO::PARAM_INT);
            $logo_stmt->execute();
            $logo_data = $logo_stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($conn instanceof mysqli) {
            $logo_stmt = $conn->prepare($logo_query);
            $logo_stmt->bind_param("i", $id);
            $logo_stmt->execute();
            $result = $logo_stmt->get_result();
            $logo_data = $result->fetch_assoc();
            $logo_stmt->close();
        } else {
            throw new Exception("Unsupported database connection type");
        }
        
        if ($logo_data && $logo_data['logo_url']) {
            $logo_path = dirname(dirname(__DIR__)) . '/' . $logo_data['logo_url'];
            if (file_exists($logo_path)) {
                unlink($logo_path);
            }
        }
        
        // Delete the partner
        $delete_query = "DELETE FROM partners WHERE id = ?";
        
        if ($conn instanceof PDO) {
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bindParam(1, $id, PDO::PARAM_INT);
            $result = $delete_stmt->execute();
        } elseif ($conn instanceof mysqli) {
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $id);
            $result = $delete_stmt->execute();
            $delete_stmt->close();
        } else {
            throw new Exception("Unsupported database connection type");
        }
        
        if ($result) {
            header('Location: index.php?deleted=1');
            exit();
        }
    } catch (Exception $e) {
        error_log("Partner deletion error: " . $e->getMessage());
        $error_message = "Failed to delete partner. " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partners - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>/assets/css/variables.css">
    <style>
        :root {
            --primary-color: #f59824;
        }
        .sidebar {
            background-color: #343a40;
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 0.5rem 1rem;
            margin: 0.2rem 0;
        }
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        .main-content {
            padding: 20px;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #e48920;
            border-color: #e48920;
        }
        .partner-card {
            transition: transform var(--transition-speed) ease;
        }
        .partner-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-box-shadow);
        }
        .partner-logo {
            height: 150px;
            object-fit: contain;
            border-radius: var(--card-border-radius);
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <h3 class="text-center mb-4">Admin Panel</h3>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/events/">
                            <i class="fas fa-calendar-alt me-2"></i>Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/donations/">
                            <i class="fas fa-hand-holding-heart me-2"></i>Donations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/team/">
                            <i class="fas fa-users me-2"></i>Team
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/admin/partners/">
                            <i class="fas fa-handshake me-2"></i>Partners
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Partners</h2>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Partner
                    </a>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
                        <?php if (DEBUG_MODE && isset($error_details)): ?>
                            <details class="mt-2">
                                <summary>Technical Details</summary>
                                <pre><?php echo htmlspecialchars($error_details); ?></pre>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['created'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Partner added successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['updated'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Partner updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Partner deleted successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($error_message) && (!isset($partners) || empty($partners))): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                        <h4>No partners found</h4>
                        <p class="text-muted">Click the "Add Partner" button to add your first partner.</p>
                    </div>
                <?php elseif (!empty($partners)): ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($partners as $partner): ?>
                            <div class="col">
                                <div class="card h-100 partner-card position-relative">
                                    <?php if ($partner['status'] === 'inactive'): ?>
                                        <span class="badge bg-warning status-badge">Inactive</span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($partner['logo_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($partner['logo_url']); ?>" 
                                             class="card-img-top partner-logo" 
                                             alt="<?php echo htmlspecialchars($partner['name']); ?> Logo">
                                    <?php endif; ?>
                                    
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($partner['name']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($partner['description'] ?? 'No description'); ?></p>
                                    </div>
                                    
                                    <div class="card-footer bg-transparent d-flex justify-content-between">
                                        <a href="edit.php?id=<?php echo $partner['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </a>
                                        <a href="index.php?delete=<?php echo $partner['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger delete-partner" 
                                           onclick="return confirm('Are you sure you want to delete this partner?');">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-close alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>
