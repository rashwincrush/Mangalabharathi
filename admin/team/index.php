<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Check login
checkLogin();

// Get all team members ordered by display_order
try {
    $conn = Database::getInstance()->getConnection();
    
    // Get all team members ordered by display_order
    $sql = "SELECT * FROM team_members ORDER BY display_order ASC, name ASC";
    
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($conn instanceof mysqli) {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $team_members = [];
        while ($row = $result->fetch_assoc()) {
            $team_members[] = $row;
        }
        $stmt->close();
    } else {
        throw new Exception("Unsupported database connection type");
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Team members query error: " . $e->getMessage());
    
    // Display a user-friendly error message
    $error_message = "An error occurred while fetching team members. Please check the database configuration.";
    $team_members = [];
}

// Handle delete request
if (isset($_GET['delete'])) {
    try {
        $id = (int)$_GET['delete'];
        
        // Get image URL before deleting
        $image_query = "SELECT image FROM team_members WHERE id = ?";
        
        if ($conn instanceof PDO) {
            $image_stmt = $conn->prepare($image_query);
            $image_stmt->bindParam(1, $id, PDO::PARAM_INT);
            $image_stmt->execute();
            $image_data = $image_stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($conn instanceof mysqli) {
            $image_stmt = $conn->prepare($image_query);
            $image_stmt->bind_param("i", $id);
            $image_stmt->execute();
            $result = $image_stmt->get_result();
            $image_data = $result->fetch_assoc();
            $image_stmt->close();
        } else {
            throw new Exception("Unsupported database connection type");
        }
        
        if ($image_data && $image_data['image']) {
            $image_path = dirname(dirname(__DIR__)) . '/' . $image_data['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Delete the team member
        $delete_query = "DELETE FROM team_members WHERE id = ?";
        
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
        error_log("Team member deletion error: " . $e->getMessage());
        $error_message = "Failed to delete team member. " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Management - <?php echo SITE_NAME; ?></title>
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
        .team-member-card {
            transition: transform var(--transition-speed) ease;
        }
        .team-member-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-box-shadow);
        }
        .team-member-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin: 0 auto;
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
                        <a class="nav-link" href="/admin/index.php">
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
                        <a class="nav-link active" href="/admin/team/">
                            <i class="fas fa-users me-2"></i>Team
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/partners/">
                            <i class="fas fa-handshake me-2"></i>Partners
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-danger" href="../admin/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Team Members</h2>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Team Member
                    </a>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['created'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Team member added successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['updated'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Team member updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Team member deleted successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($team_members)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h4>No team members found</h4>
                        <p class="text-muted">Click the "Add Team Member" button to add your first team member.</p>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($team_members as $member): ?>
                            <?php 
                            // Safely handle image URL
                            $image_url = !empty($member['image']) ? htmlspecialchars($member['image']) : '/assets/images/default-avatar.png';
                            
                            // Safely handle other fields
                            $full_name = !empty($member['name']) ? htmlspecialchars($member['name']) : 'Unnamed';
                            $position = !empty($member['role']) ? htmlspecialchars($member['role']) : '';
                            $bio = !empty($member['bio']) ? htmlspecialchars($member['bio']) : '';
                            $email = !empty($member['email']) ? htmlspecialchars($member['email']) : '';
                            $linkedin_url = !empty($member['linkedin_url']) ? htmlspecialchars($member['linkedin_url']) : '';
                            ?>
                            <div class="col">
                                <div class="card h-100 team-member-card">
                                    <?php if ($member['status'] === 'inactive'): ?>
                                        <div class="inactive-overlay">Inactive</div>
                                    <?php endif; ?>
                                    
                                    <img src="<?php echo $image_url; ?>" 
                                         alt="<?php echo $full_name; ?>" 
                                         class="team-member-image">
                                    
                                    <div class="card-body">
                                        <h5 class="card-title mt-3"><?php echo $full_name; ?></h5>
                                        <?php if ($position): ?>
                                            <p class="card-text text-muted"><?php echo $position; ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if ($bio): ?>
                                            <p class="card-text small"><?php echo nl2br($bio); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-footer bg-transparent">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <?php if ($email): ?>
                                                <a href="mailto:<?php echo $email; ?>" class="text-muted">
                                                    <i class="fas fa-envelope"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($linkedin_url): ?>
                                                <a href="<?php echo $linkedin_url; ?>" target="_blank" class="text-muted">
                                                    <i class="fab fa-linkedin"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <div>
                                                <a href="edit.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="index.php?delete=<?php echo $member['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger delete-team-member"
                                                   onclick="return confirm('Are you sure you want to delete this team member?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
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
