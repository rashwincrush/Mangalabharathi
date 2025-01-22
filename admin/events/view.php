<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Event - <?php echo SITE_NAME; ?></title>
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
        .event-card {
            transition: transform var(--transition-speed) ease;
        }
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-box-shadow);
        }
        .media-gallery img, .media-gallery video {
            max-width: 100%;
            height: auto;
            margin-bottom: 15px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        :root {
            --primary-color: #f59824;
            --primary-dark: #e48920;
            --text-muted: #6c757d;
        }
        
        /* Enhanced Styles */
        .event-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .event-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .metric-card {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
        }
        
        .metric-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            margin-right: 1rem;
            font-size: 1.5rem;
        }
        
        .metric-content {
            flex-grow: 1;
        }
        
        .metric-content h4 {
            margin-bottom: 0.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .metric-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
        }
        
        .impact-description {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }
        
        .content-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .section-title {
            color: #212529;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .media-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .media-item {
            flex: 0 0 auto;
            width: 250px;  
            height: 150px; 
            overflow: hidden;
            cursor: pointer;
        }

        .media-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;  
            transition: transform 0.3s ease;
        }

        .media-item:hover img {
            transform: scale(1.1);
        }

        .lightbox-content img {
            max-width: 90vw;
            max-height: 90vh;
            object-fit: contain;
        }

        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1050;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }
        
        .lightbox-content {
            max-width: 90%;
            max-height: 90%;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        
        .lightbox-content img {
            max-width: 90vw;
            max-height: 90vh;
            object-fit: contain;
        }

        #lightbox-navigation {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            transform: translateY(-50%);
            display: flex;
            justify-content: space-between;
            pointer-events: none;
        }

        .nav-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 50%;
            pointer-events: auto;
            transition: background 0.3s ease;
        }

        .nav-btn:hover {
            background: rgba(255,255,255,0.4);
        }

        .nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .nav-prev {
            left: 20px;
        }

        .nav-next {
            right: 20px;
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            z-index: 1060;
        }
        
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1050;
            justify-content: center;
            align-items: center;
        }

        .lightbox-content {
            position: relative;
            max-width: 90vw;
            max-height: 90vh;
        }

        .lightbox-content img {
            max-height: 90vh;
            max-width: 90vw;
            object-fit: contain;
        }

        .image-counter {
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            background: rgba(0, 0, 0, 0.5);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
        }

        .nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .nav-prev {
            left: 20px;
        }

        .nav-next {
            right: 20px;
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            z-index: 1060;
        }

        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1050;
            justify-content: center;
            align-items: center;
        }

        .lightbox-content {
            position: relative;
            max-width: 90vw;
            max-height: 90vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .lightbox-content img {
            max-height: 90vh;
            max-width: 90vw;
            object-fit: contain;
        }

        .image-counter {
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            background: rgba(0, 0, 0, 0.5);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
        }

        #lightbox-navigation {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            transform: translateY(-50%);
            pointer-events: none;
            z-index: 1060;
        }

        .nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
            pointer-events: auto;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .nav-prev {
            left: 20px;
        }

        .nav-next {
            right: 20px;
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            z-index: 1060;
        }
    </style>
    <?php echo '<meta name="csrf-token" content="' . generate_csrf_token() . '">'; ?>
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
                    <h2>View Event</h2>
                    <div>
                        <div class="btn-group me-2">
                            <a href="/admin/events/Upcoming_events.php" class="btn btn-primary">
                                <i class="fas fa-calendar-check me-2"></i>Upcoming Events
                            </a>
                            <a href="/admin/events/Past_events.php" class="btn btn-secondary">
                                <i class="fas fa-history me-2"></i>Past Events
                            </a>
                        </div>
                        <a href="/admin/events/create.php" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Create Event
                        </a>
                    </div>
                </div>

                <?php
                require_once '../../includes/config.php';
                require_once '../../includes/db.php';
                require_once '../includes/auth.php';

                // Ensure database connection
                if (!isset($conn) || $conn === null) {
                    try {
                        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
                        
                        // Check connection
                        if ($conn->connect_error) {
                            throw new Exception("Connection failed: " . $conn->connect_error);
                        }
                    } catch (Exception $e) {
                        // Log the error
                        error_log("Database Connection Error in view.php: " . $e->getMessage());
                        
                        // Set error message
                        $error = "Unable to connect to the database. Please try again later.";
                        
                        // Redirect or show error
                        header('Location: index.php?error=database_connection');
                        exit();
                    }
                }

                // Check login
                checkLogin();

                // Initialize variables
                $event = null;
                $media = [];
                $error = '';

                // Get event ID from URL
                $event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

                if ($event_id <= 0) {
                    header('Location: index.php');
                    exit();
                }

                // Additional query to get all event details including category and impact metrics
                $query = "
                    SELECT e.*, 
                           c.category,
                           m.id as media_id, m.media_type, m.media_url,
                           im.people_helped, im.volunteers, im.impact_description
                    FROM events e
                    LEFT JOIN categories c ON e.category_id = c.id
                    LEFT JOIN event_media m ON e.id = m.event_id
                    LEFT JOIN event_impact_metrics im ON e.id = im.event_id
                    WHERE e.id = ?";

                try {
                    // Prepare and execute the query
                    $stmt = $conn->prepare($query);
                    
                    if (!$stmt) {
                        throw new Exception("Query preparation failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param('i', $event_id);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Query execution failed: " . $stmt->error);
                    }
                    
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $event = $result->fetch_assoc();

                        // Fetch all media for this event
                        $media_query = "SELECT * FROM event_media WHERE event_id = ?";
                        $media_stmt = $conn->prepare($media_query);
                        
                        if (!$media_stmt) {
                            throw new Exception("Media query preparation failed: " . $conn->error);
                        }
                        
                        $media_stmt->bind_param('i', $event_id);
                        
                        if (!$media_stmt->execute()) {
                            throw new Exception("Media query execution failed: " . $media_stmt->error);
                        }
                        
                        $media_result = $media_stmt->get_result();

                        while ($media_row = $media_result->fetch_assoc()) {
                            $media[] = [
                                'id' => $media_row['id'],
                                'media_type' => $media_row['media_type'],
                                'media_url' => $media_row['media_url'],
                                'thumbnail' => $media_row['thumbnail_url'] ?? $media_row['media_url']
                            ];
                        }

                        $media_stmt->close();
                    } else {
                        $error = "Event not found.";
                    }

                    $stmt->close();
                } catch (Exception $e) {
                    // Log the error
                    error_log("Event View Error: " . $e->getMessage());
                    
                    // Set error message
                    $error = "An error occurred while retrieving event details. Please try again later.";
                }
                ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php else: ?>
                    <!-- Event Header -->
                    <div class="event-header">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <span class="event-status <?php echo $event['status'] === 'upcoming' ? 'bg-primary' : 'bg-success'; ?> text-white">
                                    <?php echo ucfirst($event['status']); ?>
                                </span>
                                <h1 class="display-5 mb-2"><?php echo htmlspecialchars($event['title']); ?></h1>
                                <?php if (!empty($event['category'])): ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($event['category']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="edit.php?id=<?php echo $event['id']; ?>" class="btn btn-primary me-2">
                                    <i class="fas fa-edit me-2"></i>Edit Event
                                </a>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Events
                                </a>
                            </div>
                        </div>

                        <!-- Key Information Grid -->
                        <div class="info-grid">
                            <div class="info-card">
                                <i class="far fa-calendar-alt info-icon"></i>
                                <div class="info-value">
                                    <?php echo date('F d, Y', strtotime($event['event_date'])); ?>
                                </div>
                                <div class="info-label">Date</div>
                            </div>
                            
                            <div class="info-card">
                                <i class="fas fa-map-marker-alt info-icon"></i>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($event['location']); ?>
                                </div>
                                <div class="info-label">Location</div>
                            </div>
                            
                            <?php if ($event['status'] === 'past' && !empty($event['people_helped'])): ?>
                            <div class="info-card">
                                <i class="fas fa-users info-icon"></i>
                                <div class="info-value">
                                    <?php echo number_format($event['people_helped']); ?>
                                </div>
                                <div class="info-label">People Helped</div>
                            </div>
                            
                            <div class="info-card">
                                <i class="fas fa-hands-helping info-icon"></i>
                                <div class="info-value">
                                    <?php echo number_format($event['volunteers']); ?>
                                </div>
                                <div class="info-label">Volunteers</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Event Details Section -->
                    <div class="content-section">
                        <h3 class="section-title">
                            <i class="fas fa-info-circle me-2"></i>
                            Event Details
                        </h3>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <h5>Description</h5>
                                        <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                    </div>
                                    
                                    <?php if (!empty($event['impact_description'])): ?>
                                    <div class="timeline-item">
                                        <h5>Impact</h5>
                                        <p><?php echo nl2br(htmlspecialchars($event['impact_description'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($event['status'] === 'past' && (!empty($event['people_helped']) || !empty($event['volunteers']))): ?>
                            <div class="col-md-4">
                                <div class="metric-card mb-3">
                                    <div class="metric-icon">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <div class="metric-content">
                                        <h4>People Helped</h4>
                                        <p class="metric-value"><?php echo number_format($event['people_helped']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="metric-card">
                                    <div class="metric-icon">
                                        <i class="fas fa-hands-helping"></i>
                                    </div>
                                    <div class="metric-content">
                                        <h4>Volunteers</h4>
                                        <p class="metric-value"><?php echo number_format($event['volunteers']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Impact Metrics Section -->
                    <div class="content-section">
                        <h3 class="section-title">
                            <i class="fas fa-chart-line me-2"></i>
                            Event Impact
                        </h3>
                        <?php if (!empty($event['people_helped']) || !empty($event['volunteers']) || !empty($event['impact_description'])): ?>
                            <div class="row">
                                <?php if (!empty($event['people_helped'])): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="metric-card">
                                            <div class="metric-icon">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div class="metric-content">
                                                <h4>People Helped</h4>
                                                <p class="metric-value"><?php echo htmlspecialchars($event['people_helped']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($event['volunteers'])): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="metric-card">
                                            <div class="metric-icon">
                                                <i class="fas fa-hands-helping"></i>
                                            </div>
                                            <div class="metric-content">
                                                <h4>Volunteers</h4>
                                                <p class="metric-value"><?php echo htmlspecialchars($event['volunteers']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($event['impact_description'])): ?>
                                <div class="impact-description mt-3">
                                    <h4>Impact Description</h4>
                                    <p><?php echo htmlspecialchars($event['impact_description']); ?></p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No impact metrics available for this event.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Media Section -->
                    <?php if (!empty($media)): ?>
                    <div class="content-section">
                        <h3 class="section-title">
                            <i class="fas fa-images me-2"></i>
                            Event Gallery
                        </h3>
                        <div class="media-gallery d-flex flex-wrap">
                            <?php 
                            // Combine and shuffle images and videos
                            $combined_media = array_merge(
                                array_filter($media, function($item) { 
                                    return $item['media_type'] === 'image'; 
                                }),
                                array_filter($media, function($item) { 
                                    return $item['media_type'] === 'video'; 
                                })
                            );
                            
                            // Display combined media
                            $imageCount = 0;
                            foreach ($combined_media as $item): ?>
                                <?php if ($item['media_type'] === 'image'): ?>
                                    <div class="media-item" onclick="openLightbox('<?php echo htmlspecialchars($item['media_type']); ?>', '<?php echo htmlspecialchars($item['media_url']); ?>', <?php echo $imageCount; ?>)">
                                        <img src="/<?php echo htmlspecialchars($item['thumbnail']); ?>" 
                                             alt="Event Image" 
                                             class="img-fluid">
                                    </div>
                                    <?php $imageCount++; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Separate Video Section -->
                    <div class="content-section video-section">
                        <h3 class="section-title">
                            <i class="fab fa-youtube me-2"></i>
                            Event Videos
                        </h3>
                        <div class="d-flex flex-wrap">
                            <?php 
                            foreach ($combined_media as $item): ?>
                                <?php if ($item['media_type'] === 'video'): ?>
                                    <div class="youtube-preview">
                                        <?php 
                                        // Extract YouTube video ID
                                        $video_id = '';
                                        if (preg_match('/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $item['media_url'], $matches)) {
                                            $video_id = $matches[1];
                                        }
                                        ?>
                                        <?php if ($video_id): ?>
                                            <iframe 
                                                src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video_id); ?>" 
                                                title="YouTube Video" 
                                                frameborder="0" 
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                allowfullscreen>
                                            </iframe>
                                        <?php else: ?>
                                            <div class="alert alert-warning">
                                                Unable to embed video. Invalid YouTube URL.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Lightbox -->
    <div id="lightbox" class="lightbox" onclick="closeLightbox()">
        <button class="close-btn" onclick="closeLightbox()">
            <i class="fas fa-times"></i>
        </button>
        <div id="lightbox-navigation"></div>
        <div id="lightbox-content" class="lightbox-content" onclick="event.stopPropagation()">
            <!-- Dynamic content will be inserted here -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Global variables to track lightbox state
        let currentImageIndex = 0;
        let imagesList = [];

        // Initialize the lightbox functionality
        function initializeLightbox() {
            // Get all media items
            const mediaItems = document.querySelectorAll('.media-item');
            
            // Reset the imagesList
            imagesList = [];
            
            // Build the images list from media items
            mediaItems.forEach((item, index) => {
                const img = item.querySelector('img');
                if (img) {
                    const fullSrc = img.src.replace('/thumbnail/', '/');
                    imagesList.push({
                        src: fullSrc,
                        thumbnail: img.src,
                        alt: img.alt || 'Event Image'
                    });
                    
                    // Add click handler
                    item.onclick = () => openLightbox(index);
                }
            });

            console.log('Initialized imagesList:', imagesList); // Debug log
        }

        // Open the lightbox with a specific image
        function openLightbox(index) {
            const lightbox = document.getElementById('lightbox');
            currentImageIndex = index;
            
            console.log('Opening lightbox with index:', index); // Debug log
            
            updateLightboxContent();
            lightbox.style.display = 'flex';
            
            // Add keyboard event listeners
            document.addEventListener('keydown', handleKeyboardNavigation);
        }

        // Update the lightbox content
        function updateLightboxContent() {
            const lightboxContent = document.getElementById('lightbox-content');
            const navigation = document.getElementById('lightbox-navigation');
            
            if (!imagesList[currentImageIndex]) {
                console.error('No image found at index:', currentImageIndex); // Debug log
                return;
            }
            
            console.log('Updating content with image:', imagesList[currentImageIndex]); // Debug log

            // Update main image
            lightboxContent.innerHTML = `
                <img src="${imagesList[currentImageIndex].src}" 
                     alt="${imagesList[currentImageIndex].alt}" 
                     class="img-fluid">
                <div class="image-counter">
                    ${currentImageIndex + 1} / ${imagesList.length}
                </div>
            `;

            // Update navigation
            navigation.innerHTML = `
                <button onclick="navigateImage(-1)" 
                        class="nav-btn nav-prev" 
                        ${currentImageIndex === 0 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button onclick="navigateImage(1)" 
                        class="nav-btn nav-next"
                        ${currentImageIndex === imagesList.length - 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;
        }

        // Navigate to next/previous image
        function navigateImage(direction) {
            event.stopPropagation(); // Prevent lightbox from closing
            
            const newIndex = currentImageIndex + direction;
            console.log('Navigating from', currentImageIndex, 'to', newIndex); // Debug log
            
            if (newIndex >= 0 && newIndex < imagesList.length) {
                currentImageIndex = newIndex;
                updateLightboxContent();
            }
        }

        // Handle keyboard navigation
        function handleKeyboardNavigation(event) {
            switch(event.key) {
                case 'ArrowLeft':
                    navigateImage(-1);
                    break;
                case 'ArrowRight':
                    navigateImage(1);
                    break;
                case 'Escape':
                    closeLightbox();
                    break;
            }
        }

        // Close the lightbox
        function closeLightbox() {
            const lightbox = document.getElementById('lightbox');
            lightbox.style.display = 'none';
            document.removeEventListener('keydown', handleKeyboardNavigation);
        }

        // Initialize lightbox when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            initializeLightbox();
            console.log('Lightbox initialized'); // Debug log
        });
    </script>
</body>
</html>
