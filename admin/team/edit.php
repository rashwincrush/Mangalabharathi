<?php
require_once '../../includes/config.php';
require_once '../includes/auth.php';

// Ensure only logged-in admins can access
checkLogin();

$error = '';
$success = '';
$member = null;

// Validate and sanitize ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    $error = 'Invalid team member ID';
}

$conn = get_db_connection();

// Fetch existing member details
if (empty($error)) {
    $stmt = $conn->prepare("SELECT * FROM team_members WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = 'Team member not found';
    } else {
        $member = $result->fetch_assoc();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    // Sanitize inputs
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $position = $conn->real_escape_string($_POST['position']);
    $bio = $conn->real_escape_string($_POST['bio']);
    $email = $conn->real_escape_string($_POST['email']);
    $linkedin_url = $conn->real_escape_string($_POST['linkedin_url']);
    $display_order = intval($_POST['display_order']);
    $status = $conn->real_escape_string($_POST['status']);
    
    // Handle file upload
    $image_url = $member['image_url'];
    if (!empty($_FILES['image']['name'])) {
        $upload_dir = '../../assets/images/team/';
        $filename = uniqid() . '_' . basename($_FILES['image']['name']);
        $upload_path = $upload_dir . $filename;
        $relative_path = '/assets/images/team/' . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            // Delete old photo if exists and is different
            if (!empty($member['image_url']) && file_exists('../../' . ltrim($member['image_url'], '/'))) {
                unlink('../../' . ltrim($member['image_url'], '/'));
            }
            $image_url = $relative_path;
        } else {
            $error = 'Failed to upload photo';
        }
    }
    
    // Update team member
    if (empty($error)) {
        $stmt = $conn->prepare("UPDATE team_members SET 
                                 full_name = ?, 
                                 position = ?, 
                                 bio = ?, 
                                 image_url = ?, 
                                 email = ?, 
                                 linkedin_url = ?, 
                                 display_order = ?, 
                                 status = ? 
                                 WHERE id = ?");
        $stmt->bind_param("ssssssssi", 
            $full_name, 
            $position, 
            $bio, 
            $image_url, 
            $email, 
            $linkedin_url, 
            $display_order, 
            $status, 
            $id);
        
        if ($stmt->execute()) {
            $success = 'Team member updated successfully!';
            // Refresh member data
            $stmt = $conn->prepare("SELECT * FROM team_members WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $member = $result->fetch_assoc();
        } else {
            $error = 'Failed to update team member: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Team Member - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo ADMIN_URL; ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit Team Member</h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if ($member): ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <?php if (!empty($member['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($member['image_url']); ?>" 
                                     alt="Current Photo" 
                                     class="img-fluid rounded-circle mb-3" 
                                     style="max-width: 200px; max-height: 200px; object-fit: cover;">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($member['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="position" class="form-label">Position</label>
                                    <input type="text" class="form-control" id="position" name="position" 
                                           value="<?php echo htmlspecialchars($member['position']); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio"><?php echo htmlspecialchars($member['bio']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($member['email']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="linkedin_url" class="form-label">LinkedIn URL</label>
                                <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" 
                                       value="<?php echo htmlspecialchars($member['linkedin_url']); ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="image" class="form-label">Update Profile Photo</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                    <small class="text-muted">Recommended: Square image, max 2MB</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="display_order" class="form-label">Display Order</label>
                                    <input type="number" class="form-control" id="display_order" name="display_order" 
                                           value="<?php echo intval($member['display_order']); ?>" 
                                           min="0" required>
                                    <small class="text-muted">Lower numbers appear first</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php if ($member['status'] == 'active') echo 'selected'; ?>>Active</option>
                                    <option value="inactive" <?php if ($member['status'] == 'inactive') echo 'selected'; ?>>Inactive</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Team Member</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
