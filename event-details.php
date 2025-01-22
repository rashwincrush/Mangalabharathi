<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/EventManager.php';

// Initialize database connection
$conn = Database::getInstance()->getConnection();
$eventManager = new EventManager($conn);

// Check if event ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect to our-journey page if no valid ID
    error_log("Invalid event ID: " . ($_GET['id'] ?? 'No ID provided'));
    header('Location: our-journey.php');
    exit;
}

$event_id = intval($_GET['id']);
error_log("Attempting to fetch event with ID: $event_id");

try {
    // Fetch event details
    $event_query = "SELECT 
        *,
        DATE_FORMAT(event_date, '%Y') as year,
        DATE_FORMAT(event_date, '%b') as month,
        DATE_FORMAT(event_date, '%d') as day,
        DATE_FORMAT(event_date, '%h:%i %p') as time
        FROM events 
        WHERE id = ?";
    
    $stmt = $conn->prepare($event_query);
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // No event found
        error_log("No event found for ID: $event_id");
        error_log("Full GET data: " . print_r($_GET, true));
        header('Location: our-journey.php');
        exit;
    }
    
    $event = $result->fetch_assoc();
    error_log("Fetched Event Details: " . print_r($event, true));
    
    // Fetch related media
    $media_query = "SELECT media_url, media_type FROM event_media WHERE event_id = ?";
    $media_stmt = $conn->prepare($media_query);
    $media_stmt->bind_param('i', $event_id);
    $media_stmt->execute();
    $media_result = $media_stmt->get_result();
    $media_items = $media_result->fetch_all(MYSQLI_ASSOC);
    
    // Log media items for debugging
    error_log("Event ID: $event_id");
    error_log("Media Items: " . print_r($media_items, true));
    
} catch (Exception $e) {
    // Log error and redirect
    error_log("Event Details Error: " . $e->getMessage());
    header('Location: our-journey.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> | Managalabhrathi Trust</title>
    
    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="assets/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="event-details-header text-center mb-5">
                    <h1><?php echo htmlspecialchars($event['title']); ?></h1>
                    
                    <div class="event-meta text-muted mb-3">
                        <span>
                            <i class="fas fa-calendar me-2"></i>
                            <?php echo htmlspecialchars($event['month'] . ' ' . $event['day'] . ', ' . $event['year']); ?>
                        </span>
                        <?php if (!empty($event['location'])): ?>
                            <span class="ms-3">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <?php echo htmlspecialchars($event['location']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php
                    $categoryClass = match($event['category'] ?? 'default') {
                        'education' => 'bg-primary',
                        'health' => 'bg-success',
                        'community' => 'bg-info',
                        'environment' => 'bg-warning',
                        default => 'bg-secondary'
                    };
                    ?>
                    <span class="badge <?php echo $categoryClass; ?>">
                        <?php echo htmlspecialchars(ucfirst($event['category'] ?? 'General')); ?>
                    </span>
                </div>

                <?php if (!empty($event['image'])): ?>
                    <div class="event-main-image mb-4">
                        <img src="uploads/events/<?php echo htmlspecialchars($event['image']); ?>" 
                             class="img-fluid rounded" 
                             alt="<?php echo htmlspecialchars($event['title']); ?>">
                    </div>
                <?php endif; ?>

                <div class="event-description">
                    <h3>Event Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                </div>

                <?php if (!empty($media_items)): ?>
                    <div class="event-media mt-5">
                        <h3>Event Gallery</h3>
                        <div class="row">
                            <?php foreach ($media_items as $media): ?>
                                <div class="col-md-4 mb-3">
                                    <?php 
                                    // Safely handle media types
                                    $media_url = htmlspecialchars($media['media_url'] ?? '');
                                    $media_type = htmlspecialchars($media['media_type'] ?? '');
                                    
                                    if ($media_type === 'image'): ?>
                                        <img src="uploads/events/<?php echo $media_url; ?>" 
                                             class="img-fluid rounded" 
                                             alt="Event Media">
                                    <?php elseif ($media_type === 'video'): ?>
                                        <video controls class="img-fluid rounded">
                                            <source src="uploads/events/<?php echo $media_url; ?>" 
                                                    type="video/<?php echo $media_type; ?>">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="event-actions mt-4 text-center">
                    <a href="our-journey.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Events
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
