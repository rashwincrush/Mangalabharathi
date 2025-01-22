<?php
// Ensure config is loaded first
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/EventManager.php';
require_once '../includes/auth.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging function
if (!function_exists('debugLog')) {
    function debugLog($message) {
        error_log("[CREATE EVENT DEBUG] " . $message);
        echo "<!-- DEBUG: " . htmlspecialchars($message) . " -->";
    }
}

// Manually define CSRF token generation if not already defined
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate or retrieve existing token
        if (!isset($_SESSION['CSRF_TOKEN'])) {
            $_SESSION['CSRF_TOKEN'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['CSRF_TOKEN'];
    }
}

// Manually define CSRF token verification if not already defined
if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verify token
        return isset($_SESSION['CSRF_TOKEN']) && hash_equals($_SESSION['CSRF_TOKEN'], $token);
    }
}

// Ensure CSRF token is generated early
$csrf_token = generate_csrf_token();
debugLog("CSRF Token Generated: $csrf_token");

try {
    debugLog("Starting script execution");
    
    // Check if config files exist
    $config_files = [
        '../../includes/config.php',
        '../../includes/db.php',
        '../../includes/EventManager.php',
        '../includes/auth.php'
    ];
    
    foreach ($config_files as $file) {
        if (!file_exists($file)) {
            debugLog("Missing file: $file");
            throw new Exception("Required configuration file not found: $file");
        }
    }

    debugLog("All required files included successfully");

    // Security constants
    define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
    define('ALLOWED_VIDEO_EXTENSIONS', ['mp4', 'avi', 'mov']);
    define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

    // Check login
    try {
        checkLogin();
        debugLog("Login check passed");
    } catch (Exception $e) {
        debugLog("Login check failed: " . $e->getMessage());
        throw $e;
    }

    // Get database connection
    try {
        $conn = Database::getInstance()->getConnection();
        if (!$conn) {
            throw new Exception("Failed to establish database connection");
        }
        debugLog("Database connection established");
    } catch (Exception $e) {
        debugLog("Database connection error: " . $e->getMessage());
        throw $e;
    }

    // Determine event type
    $eventType = $_GET['type'] ?? 'upcoming';
    $isPastEvent = $eventType === 'past';
    debugLog("Event type: $eventType");

    // Fetch categories
    try {
        $categories_query = "SELECT id, category FROM categories ORDER BY category";
        $categories_result = $conn->query($categories_query);
        $categories = [];
        while ($row = $categories_result->fetch_assoc()) {
            $categories[] = $row;
        }
        debugLog("Categories fetched: " . count($categories));
    } catch (Exception $e) {
        debugLog("Category fetch error: " . $e->getMessage());
        $categories = [];
    }

    // Handle form submission
    $error = '';
    $success = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        debugLog("POST request received");
        try {
            // Sanitize and validate input
            $data = [
                'title' => sanitizeInput($_POST['title'] ?? ''),
                'description' => sanitizeInput($_POST['description'] ?? ''),
                'event_date' => $_POST['event_date'] ?? '',
                'location' => sanitizeInput($_POST['location'] ?? ''),
                'category_id' => (int)($_POST['category'] ?? null),
                'status' => $isPastEvent ? 'past' : 'upcoming'
            ];

            debugLog("Processed form data: " . print_r($data, true));

            // Validate required fields
            if (empty($data['title']) || empty($data['event_date'])) {
                throw new Exception("Title and Event Date are required.");
            }

            // Additional fields based on event type
            if ($isPastEvent) {
                $data['people_helped'] = (int)($_POST['people_helped'] ?? 0);
                $data['volunteers'] = (int)($_POST['volunteers'] ?? 0);
                $data['impact_description'] = sanitizeInput($_POST['impact_description'] ?? '');
            } else {
                $data['expected_beneficiaries'] = (int)($_POST['expected_beneficiaries'] ?? 0);
                $data['volunteers_required'] = (int)($_POST['volunteers_required'] ?? 0);
                $data['partner'] = sanitizeInput($_POST['partner'] ?? '');
                $data['donation_link'] = filter_var($_POST['donation_link'] ?? '', FILTER_VALIDATE_URL) ?: null;
            }

            // Begin transaction
            $conn->begin_transaction();

            // Generate unique event ID
            $event_id = bin2hex(random_bytes(16));
            $data['id'] = $event_id;

            // Prepare and execute event insertion
            $columns = array_keys($data);
            $values = array_values($data);
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            
            $query = "INSERT INTO events (" . implode(',', $columns) . ") VALUES ($placeholders)";
            $stmt = $conn->prepare($query);
            
            // Dynamically bind parameters
            $types = str_repeat('s', count($values)); // Assume all are strings
            $stmt->bind_param($types, ...$values);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create event: " . $stmt->error);
            }

            debugLog("Event created successfully with ID: $event_id");

            // Handle media uploads
            $media_uploads = [
                'images' => [],
                'videos' => [],
                'youtube_links' => []
            ];

            // Process image uploads
            if (!empty($_FILES['event_images']['name'][0])) {
                $image_uploads = processFileUploads($_FILES['event_images'], 'images', ALLOWED_IMAGE_EXTENSIONS, MAX_FILE_SIZE);
                $media_uploads['images'] = $image_uploads['uploaded_files'];
                debugLog("Image uploads: " . print_r($media_uploads['images'], true));
            }

            // Process video uploads
            if (!empty($_FILES['event_videos']['name'][0])) {
                $video_uploads = processFileUploads($_FILES['event_videos'], 'videos', ALLOWED_VIDEO_EXTENSIONS, MAX_FILE_SIZE);
                $media_uploads['videos'] = $video_uploads['uploaded_files'];
                debugLog("Video uploads: " . print_r($media_uploads['videos'], true));
            }

            // Process YouTube links for past events
            if ($isPastEvent && !empty($_POST['youtube_links'])) {
                $youtube_links = array_filter(array_map('trim', explode("\n", $_POST['youtube_links'])));
                $media_uploads['youtube_links'] = $youtube_links;
                debugLog("YouTube links: " . print_r($media_uploads['youtube_links'], true));
            }

            // Insert media
            if (!empty($media_uploads['images']) || !empty($media_uploads['videos']) || !empty($media_uploads['youtube_links'])) {
                $media_query = "INSERT INTO event_media (event_id, media_type, file_path) VALUES (?, ?, ?)";
                $media_stmt = $conn->prepare($media_query);

                // Insert images
                foreach ($media_uploads['images'] as $image) {
                    $media_stmt->bind_param("sss", $event_id, $media_type = 'image', $image);
                    $media_stmt->execute();
                }

                // Insert videos
                foreach ($media_uploads['videos'] as $video) {
                    $media_stmt->bind_param("sss", $event_id, $media_type = 'video', $video);
                    $media_stmt->execute();
                }

                // Insert YouTube links
                foreach ($media_uploads['youtube_links'] as $youtube_link) {
                    $media_stmt->bind_param("sss", $event_id, $media_type = 'youtube', $youtube_link);
                    $media_stmt->execute();
                }
            }

            // Commit transaction
            $conn->commit();
            $success = true;

            // Redirect with success message
            $_SESSION['success_message'] = "Event created successfully!";
            header("Location: index.php");
            exit();

        } catch (Exception $e) {
            // Rollback transaction
            if ($conn->inTransaction()) {
                $conn->rollback();
            }
            $error = $e->getMessage();
            debugLog("Event creation error: " . $error);
        }
    }
} catch (Exception $e) {
    debugLog("Fatal error: " . $e->getMessage());
    $error = $e->getMessage();
}

// Sanitization function
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// File upload processing function
function processFileUploads($file_input, $type, $allowed_extensions, $max_file_size) {
    $uploaded_files = [];
    $errors = [];
    $upload_dir = __DIR__ . "/../../uploads/{$type}/";

    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    foreach ($file_input['name'] as $key => $name) {
        $tmp_name = $file_input['tmp_name'][$key];
        $size = $file_input['size'][$key];
        $error = $file_input['error'][$key];

        // Skip empty uploads
        if ($error === UPLOAD_ERR_NO_FILE) continue;

        // Get file extension
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        // Validate file type and size
        if (!in_array($extension, $allowed_extensions)) {
            $errors[] = "$name: Invalid file type. Allowed types: " . implode(', ', $allowed_extensions);
            continue;
        }

        if ($size > $max_file_size) {
            $errors[] = "$name: File too large. Max size is " . ($max_file_size / 1024 / 1024) . "MB";
            continue;
        }

        // Generate unique filename
        $unique_filename = uniqid() . '.' . $extension;
        $destination = $upload_dir . $unique_filename;

        // Move uploaded file
        if (move_uploaded_file($tmp_name, $destination)) {
            $uploaded_files[] = $unique_filename;
        } else {
            $errors[] = "$name: Failed to move uploaded file";
        }
    }

    return [
        'uploaded_files' => $uploaded_files,
        'errors' => $errors
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - <?php echo defined('SITE_NAME') ? SITE_NAME : 'Admin Panel'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/admin'; ?>/assets/css/variables.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* Override or extend variables.css styles if needed */
        body {
            background-color: var(--color-background);
        }
        .main-content {
            background-color: var(--color-background-light);
            padding: var(--spacing-large);
        }
        .event-card {
            margin-bottom: var(--spacing-large);
        }
        .form-control, .form-select {
            border-radius: 6px;
            transition: all var(--transition-speed) ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.2rem rgba(245, 152, 36, 0.25);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <h3 class="text-center mb-4 text-white">Admin Panel</h3>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/admin/events/">
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
                        <a class="nav-link" href="/admin/partners/">
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
                    <div class="d-flex align-items-center">
                        <a href="/admin/events/" class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left me-2"></i>Back to Events
                        </a>
                        <h2 class="text-primary mb-0">Create New Event</h2>
                    </div>
                    <div>
                        <div class="btn-group me-2">
                            <a href="/admin/events/Upcoming_events.php" class="btn btn-secondary">
                                <i class="fas fa-calendar-check me-2"></i>Upcoming Events
                            </a>
                            <a href="/admin/events/Past_events.php" class="btn btn-secondary">
                                <i class="fas fa-history me-2"></i>Past Events
                            </a>
                        </div>
                        <a href="/admin/events/create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Event
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form id="createEventForm" method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <input type="hidden" name="event_type" value="<?php echo $eventType; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">Event Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="event_date" class="form-label">Event Date *</label>
                                    <input type="date" class="form-control" id="event_date" name="event_date" required
                                           value="<?php echo htmlspecialchars($_POST['event_date'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <div class="input-group">
                                        <select class="form-select" id="category" name="category">
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo (isset($_POST['category']) && $_POST['category'] == $category['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['category']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#newCategoryModal">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location"
                                           value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php 
                                    echo htmlspecialchars($_POST['description'] ?? ''); 
                                ?></textarea>
                            </div>

                            <?php if (!$isPastEvent): ?>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="expected_beneficiaries" class="form-label">Expected People to Benefit</label>
                                        <input type="number" class="form-control" id="expected_beneficiaries" name="expected_beneficiaries" min="0"
                                               value="<?php echo htmlspecialchars($_POST['expected_beneficiaries'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="volunteers_required" class="form-label">Volunteers Required</label>
                                        <input type="number" class="form-control" id="volunteers_required" name="volunteers_required" min="0"
                                               value="<?php echo htmlspecialchars($_POST['volunteers_required'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="partner" class="form-label">Partner</label>
                                        <input type="text" class="form-control" id="partner" name="partner"
                                               value="<?php echo htmlspecialchars($_POST['partner'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="donation_link" class="form-label">Donation Link</label>
                                        <input type="url" class="form-control" id="donation_link" name="donation_link"
                                               value="<?php echo htmlspecialchars($_POST['donation_link'] ?? ''); ?>">
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="people_helped" class="form-label">People Helped</label>
                                        <input type="number" class="form-control" id="people_helped" name="people_helped" min="0"
                                               value="<?php echo htmlspecialchars($_POST['people_helped'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="volunteers" class="form-label">Volunteers</label>
                                        <input type="number" class="form-control" id="volunteers" name="volunteers" min="0"
                                               value="<?php echo htmlspecialchars($_POST['volunteers'] ?? ''); ?>">
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="impact_description" class="form-label">Impact Description</label>
                                <textarea class="form-control" id="impact_description" name="impact_description" rows="3"><?php 
                                    echo htmlspecialchars($_POST['impact_description'] ?? ''); 
                                ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="event_images" class="form-label">Event Images</label>
                                    <input type="file" class="form-control" id="event_images" name="event_images[]" multiple 
                                           accept="image/jpeg,image/png,image/gif">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="event_videos" class="form-label">Event Videos</label>
                                    <input type="file" class="form-control" id="event_videos" name="event_videos[]" multiple 
                                           accept="video/mp4,video/avi,video/quicktime">
                                </div>
                            </div>

                            <?php if ($isPastEvent): ?>
                                <div class="mb-3">
                                    <label for="youtube_links" class="form-label">YouTube Links</label>
                                    <textarea class="form-control" id="youtube_links" name="youtube_links" rows="3" 
                                              placeholder="Enter YouTube video URLs (one per line)"><?php 
                                        echo htmlspecialchars($_POST['youtube_links'] ?? ''); 
                                    ?></textarea>
                                    <small class="text-muted">Enter one link per line</small>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Create Event
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="mt-3 text-center">
                    <a href="/admin/events/" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Events
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Add any custom JavaScript for the create event page
        document.addEventListener('DOMContentLoaded', function() {
            // Example: Form validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                // Basic validation example
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please fill in all required fields'
                    });
                }
            });
        });
    </script>
</body>
</html>
