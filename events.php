<?php
// Include necessary files
include 'includes/header.php';
include 'includes/config.php';

// Function to safely truncate text
function truncateText($text, $length = 150, $ellipsis = '...') {
    if (empty($text)) return '';
    if (strlen($text) <= $length) {
        return $text;
    }
    return rtrim(substr($text, 0, $length)) . $ellipsis;
}

// Function to safely load image
function getEventImage($image) {
    // Determine base path dynamically
    $base_path = SITE_URL . '/assets/images/events/';
    
    if (empty($image)) {
        return $base_path . 'default.jpg';
    }
    
    $imagePath = $base_path . htmlspecialchars($image);
    $defaultImage = $base_path . 'default.jpg';
    
    return $imagePath;
}

// Establish database connection
$db = Database::getInstance();
$conn = $db->getConnection();

if (!$conn) {
    die("Database connection failed");
}

// Fetch upcoming events
$upcoming_sql = "SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date";
$upcoming_stmt = $conn->prepare($upcoming_sql);
$upcoming_stmt->execute();
$result = $upcoming_stmt->get_result();
$upcoming_events = [];
while ($row = $result->fetch_assoc()) {
    $upcoming_events[] = $row;
}

// Fetch past events
$past_sql = "SELECT * FROM events WHERE event_date < CURDATE() ORDER BY event_date DESC LIMIT 6";
$past_stmt = $conn->prepare($past_sql);
$past_stmt->execute();
$result = $past_stmt->get_result();
$past_events = [];
while ($row = $result->fetch_assoc()) {
    $past_events[] = $row;
}
?>

<section class="page-header">
    <div class="container">
        <h1>Events</h1>
    </div>
</section>

<section class="events-section section">
    <div class="container">
        <div class="events-tabs">
            <button class="tab-btn active" data-tab="upcoming">Upcoming Events</button>
            <button class="tab-btn" data-tab="past">Past Events</button>
        </div>

        <div class="tab-content" id="upcoming-events">
            <div class="events-list">
                <?php
                if (!empty($upcoming_events)) {
                    foreach($upcoming_events as $row) {
                        // Safely handle potential missing keys
                        $title = htmlspecialchars($row['title'] ?? 'Untitled Event');
                        $description = truncateText(htmlspecialchars($row['description'] ?? ''));
                        $location = htmlspecialchars($row['location'] ?? 'Location Not Specified');
                        $event_date = !empty($row['event_date']) ? strtotime($row['event_date']) : time();
                        $image = getEventImage($row['image'] ?? null);
                        ?>
                        <div class="event-card">
                            <div class="event-image">
                                <img src="<?php echo $image; ?>" alt="Event Image" onerror="this.src='<?php echo SITE_URL; ?>/assets/images/events/default.jpg';">
                            </div>
                            <div class="event-details">
                                <div class="event-date">
                                    <span class="date"><?php echo date('d', $event_date); ?></span>
                                    <span class="month"><?php echo date('M', $event_date); ?></span>
                                </div>
                                <div class="event-info">
                                    <h3><?php echo $title; ?></h3>
                                    <p class="event-location"><i class="fas fa-map-marker-alt"></i> <?php echo $location; ?></p>
                                    <p class="event-description"><?php echo $description; ?></p>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p class="no-events">No upcoming events at the moment.</p>';
                }
                ?>
            </div>
        </div>

        <div class="tab-content hidden" id="past-events">
            <div class="past-events-grid">
                <?php
                if (!empty($past_events)) {
                    foreach($past_events as $row) {
                        // Safely handle potential missing keys
                        $title = htmlspecialchars($row['title'] ?? 'Untitled Event');
                        $description = truncateText(htmlspecialchars($row['description'] ?? ''));
                        $event_date = !empty($row['event_date']) ? strtotime($row['event_date']) : time();
                        $image = getEventImage($row['image'] ?? null);
                        ?>
                        <div class="past-event-card">
                            <img src="<?php echo $image; ?>" alt="Past Event" onerror="this.src='<?php echo SITE_URL; ?>/assets/images/events/default.jpg';">
                            <div class="past-event-content">
                                <h3><?php echo $title; ?></h3>
                                <p class="event-date"><i class="far fa-calendar"></i> <?php echo date('M d, Y', $event_date); ?></p>
                                <p class="event-description"><?php echo $description; ?></p>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p class="no-events">No past events to display.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</section>

<?php 
// Include footer
include 'includes/footer.php'; 
?>