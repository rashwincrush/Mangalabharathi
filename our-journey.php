<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/EventManager.php';

// Initialize database connection
$conn = Database::getInstance()->getConnection();
$eventManager = new EventManager($conn);

// Pagination settings
$events_per_page = 20; // Number of events to load per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $events_per_page;

// Get filter parameters with defaults
$selected_year = $_GET['year'] ?? 'all';
$selected_category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$view_mode = $_GET['view'] ?? 'timeline';

// Prepare base queries and parameter tracking
$where_clauses = [];
$params = [];
$param_types = '';

// Build dynamic WHERE clauses based on filters
if ($selected_year !== 'all') {
    $where_clauses[] = "YEAR(e.event_date) = ?";
    $params[] = $selected_year;
    $param_types .= 'i';
}

if ($selected_category !== 'all') {
    $where_clauses[] = "c.category = ?";
    $params[] = $selected_category;
    $param_types .= 's';
}

if (!empty($search)) {
    $where_clauses[] = "(e.title LIKE ? OR e.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= 'ss';
}

// Construct full WHERE clause
$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Fetch categories from database dynamically
$categories_query = "SELECT id, category FROM categories";
$categories = [];

if ($conn instanceof PDO) {
    $categories_stmt = $conn->prepare($categories_query);
    $categories_stmt->execute();
    while ($row = $categories_stmt->fetch(PDO::FETCH_ASSOC)) {
        // Convert to lowercase for consistent matching
        $categories[strtolower($row['category'])] = $row['category'];
    }
} elseif ($conn instanceof mysqli) {
    $categories_stmt = $conn->prepare($categories_query);
    $categories_stmt->execute();
    $result = $categories_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Convert to lowercase for consistent matching
        $categories[strtolower($row['category'])] = $row['category'];
    }
    $categories_stmt->close();
} else {
    // Fallback categories if query fails
    $categories = [
        'education' => 'Education',
        'health' => 'Health',
        'community' => 'Community',
        'environment' => 'Environment'
    ];
}

// Count total events query
$count_query = "SELECT COUNT(*) as total FROM events e LEFT JOIN categories c ON e.category_id = c.id $where_sql";

// Prepare and execute count query
if ($conn instanceof PDO) {
    $count_stmt = $conn->prepare($count_query);
    if (!empty($where_clauses)) {
        foreach ($params as $index => $param) {
            $count_stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    }
    $count_stmt->execute();
    $total_events = $count_stmt->fetchColumn();
} elseif ($conn instanceof mysqli) {
    $count_stmt = $conn->prepare($count_query);
    
    // Dynamically bind parameters
    if (!empty($where_clauses)) {
        $bind_types = str_repeat('s', count($params));
        $count_stmt->bind_param($bind_types, ...$params);
    }
    
    $count_stmt->execute();
    $result = $count_stmt->get_result();
    $total_row = $result->fetch_assoc();
    $total_events = $total_row['total'] ?? 0;
    $count_stmt->close();
} else {
    throw new Exception("Unsupported database connection type");
}

$total_pages = ceil($total_events / $events_per_page);

// Events query with pagination
$events_query = "SELECT 
    e.*,
    c.category,
    DATE_FORMAT(e.event_date, '%Y') as year,
    DATE_FORMAT(e.event_date, '%b') as month,
    DATE_FORMAT(e.event_date, '%d') as day,
    DATE_FORMAT(e.event_date, '%h:%i %p') as time
    FROM events e
    LEFT JOIN categories c ON e.category_id = c.id
    $where_sql 
    ORDER BY e.event_date DESC
    LIMIT ? OFFSET ?";

// Prepare and execute events query
if ($conn instanceof PDO) {
    $stmt = $conn->prepare($events_query);

    // Add pagination parameters
    $param_types .= 'ii';
    $params[] = $events_per_page;
    $params[] = $offset;

    // Dynamically bind parameters
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }

    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($conn instanceof mysqli) {
    $stmt = $conn->prepare($events_query);
    
    // Add pagination parameters
    $param_types .= 'ii';
    $params[] = $events_per_page;
    $params[] = $offset;

    // Dynamically bind parameters
    $bind_types = $param_types;
    $stmt->bind_param($bind_types, ...$params);
    
    $stmt->execute();
    $result = $stmt->get_result();
    $events = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    throw new Exception("Unsupported database connection type");
}

// Function to render events (used for both initial load and AJAX)
function render_events($events, $view_mode) {
    // Debug: Log events being rendered
    error_log("Rendering Events - Count: " . count($events));
    error_log("Rendering Events - View Mode: $view_mode");
    
    ob_start();
    foreach ($events as $event): 
        // Normalize category for consistent mapping
        $category = strtolower($event['category'] ?? 'general');
        $categoryClass = match($category) {
            'education' => 'bg-primary',
            'health' => 'bg-success',
            'healthcare' => 'bg-success',
            'community' => 'bg-info',
            'community service' => 'bg-info',
            'environment' => 'bg-warning',
            'women empowerment' => 'bg-danger',
            'child welfare' => 'bg-secondary',
            default => 'bg-secondary'
        };
        ?>
        <?php if ($view_mode === 'timeline'): ?>
            <div class="timeline-item" data-event-id="<?php echo $event['id']; ?>">
                <div class="timeline-content">
                    <span class="category-badge <?php echo $categoryClass; ?>">
                        <?php echo htmlspecialchars(ucfirst($event['category'] ?? 'General')); ?>
                    </span>
                    
                    <div class="timeline-date">
                        <div class="date-badge">
                            <?php echo htmlspecialchars($event['month']); ?> 
                            <?php echo htmlspecialchars($event['day']); ?>
                        </div>
                        <small class="text-muted"><?php echo htmlspecialchars($event['year']); ?></small>
                    </div>
                    
                    <div class="timeline-dot"></div>
                    
                    <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                    
                    <?php if (!empty($event['location'])): ?>
                        <p class="text-muted mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?php echo htmlspecialchars($event['location']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <p class="mb-3"><?php echo htmlspecialchars($event['description']); ?></p>
                    
                    <?php if (!empty($event['image_url'])): ?>
                        <img src="uploads/events/<?php echo htmlspecialchars($event['image_url']); ?>" 
                             class="img-fluid rounded mb-3" 
                             alt="<?php echo htmlspecialchars($event['title']); ?>">
                    <?php endif; ?>
                    
                    <div>
                        <a href="event-details.php?id=<?php echo intval($event['id']); ?>" 
                           class="btn btn-primary btn-sm">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-md-4 mb-4" data-event-id="<?php echo $event['id']; ?>">
                <div class="card h-100">
                    <?php if (!empty($event['image_url'])): ?>
                        <img src="uploads/events/<?php echo htmlspecialchars($event['image_url']); ?>" 
                             class="card-img-top" alt="Event Image"
                             style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-2"></i>
                                <?php echo $event['month']; ?> <?php echo $event['day']; ?>, <?php echo $event['year']; ?>
                            </small>
                        </p>
                        <p class="card-text"><?php echo substr(htmlspecialchars($event['description']), 0, 100) . '...'; ?></p>
                        <span class="badge <?php echo $categoryClass; ?> mb-2">
                            <?php echo htmlspecialchars(ucfirst($event['category'] ?? 'General')); ?>
                        </span>
                        <div>
                            <a href="event-details.php?id=<?php echo intval($event['id']); ?>" 
                               class="btn btn-primary btn-sm">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach;
    return ob_get_clean();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Journey - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --timeline-width: 3px;
        }

        .view-toggle {
            margin-bottom: 20px;
            text-align: right;
        }

        .timeline-container {
            padding: 2rem 0;
            position: relative;
        }

        .timeline-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: var(--timeline-width);
            height: 100%;
            background: var(--primary-color);
        }

        .timeline-item {
            width: 100%;
            margin-bottom: 3rem;
            position: relative;
        }

        .timeline-item:nth-child(odd) {
            padding-right: calc(50% + 2rem);
        }

        .timeline-item:nth-child(even) {
            padding-left: calc(50% + 2rem);
        }

        .timeline-content {
            position: relative;
            padding: 1.5rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .timeline-content:hover {
            transform: translateY(-5px);
        }

        .timeline-date {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 120px;
            text-align: center;
        }

        .timeline-item:nth-child(odd) .timeline-date {
            right: -160px;
        }

        .timeline-item:nth-child(even) .timeline-date {
            left: -160px;
        }

        .timeline-dot {
            position: absolute;
            top: 50%;
            width: 20px;
            height: 20px;
            background: var(--primary-color);
            border-radius: 50%;
            transform: translateY(-50%);
        }

        .timeline-item:nth-child(odd) .timeline-dot {
            right: -10px;
        }

        .timeline-item:nth-child(even) .timeline-dot {
            left: -10px;
        }

        .date-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .category-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            padding: 0.3rem 1rem;
            border-radius: 15px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .filter-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .gallery-item {
            transition: transform 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .gallery-item:hover {
            transform: scale(1.05);
        }

        .gallery-item img {
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
            width: 100%;
        }

        @media (max-width: 768px) {
            .timeline-container::before {
                left: 0;
            }

            .timeline-item {
                padding-left: 2rem !important;
                padding-right: 0 !important;
            }

            .timeline-dot {
                left: -10px !important;
                right: auto !important;
            }

            .timeline-date {
                position: relative !important;
                left: auto !important;
                right: auto !important;
                top: auto !important;
                transform: none !important;
                margin-bottom: 1rem;
                width: auto !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <h1 class="text-center mb-5">Our Journey</h1>

        <!-- View Toggle -->
        <div class="view-toggle">
            <div class="btn-group" role="group">
                <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'timeline'])); ?>" 
                   class="btn <?php echo $view_mode === 'timeline' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <i class="fas fa-stream me-2"></i>Timeline View
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'gallery'])); ?>" 
                   class="btn <?php echo $view_mode === 'gallery' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <i class="fas fa-th-large me-2"></i>Gallery View
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="get" class="row g-3 align-items-end">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-select" onchange="this.form.submit()">
                        <option value="all">All Years</option>
                        <?php for ($year = date('Y'); $year >= 2010; $year--): ?>
                            <option value="<?php echo $year; ?>" 
                                    <?php echo $selected_year == $year ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $value => $label): ?>
                            <option value="<?php echo $value; ?>" 
                                    <?php echo $selected_category == $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search events...">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if (empty($events)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-calendar-times fa-4x text-muted"></i>
                </div>
                <h3>No Events Found</h3>
                <p class="text-muted">Try adjusting your filters to see more events.</p>
                <a href="?year=all&category=all&view=<?php echo htmlspecialchars($view_mode); ?>" class="btn btn-primary mt-3">
                    View All Events
                </a>
            </div>
        <?php else: ?>
            <?php if ($view_mode === 'timeline'): ?>
                <div class="timeline-container">
                    <?php echo render_events($events, $view_mode); ?>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php echo render_events($events, $view_mode); ?>
                </div>
            <?php endif; ?>
            
            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>