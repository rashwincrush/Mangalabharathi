<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug output
echo "<!-- Debug Info -->";
echo "<!-- PHP Version: " . phpversion() . " -->";
echo "<!-- Current Time: " . date('Y-m-d H:i:s') . " -->";

// Detailed error logging function
function logError($message, $context = []) {
    error_log(date('[Y-m-d H:i:s] ') . $message);
    if (!empty($context)) {
        error_log('Context: ' . json_encode($context));
    }
}

// Prevent multiple connection closures
if (!function_exists('prevent_connection_closure')) {
    function prevent_connection_closure($conn) {
        // Prevent premature connection closure
        if ($conn instanceof mysqli) {
            // Disable auto-commit
            $conn->autocommit(false);
        }
    }
}

// Global error handling function
function handleGlobalError($error) {
    error_log("GLOBAL ERROR IN EVENTS INDEX: " . $error->getMessage());
    error_log("Global Error Trace: " . $error->getTraceAsString());
    
    return [
        'events' => [],
        'pagination' => [
            'total_records' => 0,
            'total_pages' => 1,
            'current_page' => 1,
            'items_per_page' => 12,
            'prev_page' => null,
            'next_page' => null
        ]
    ];
}

try {
    require_once '../../includes/config.php';
    require_once '../../includes/db.php';
    require_once '../../includes/EventManager.php';
    require_once '../includes/auth.php';

    // Check if user is logged in
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: ../login.php');
        exit();
    }

    // Get database connection
    $conn = Database::getInstance()->getConnection();
    
    // Prevent premature connection closure
    prevent_connection_closure($conn);

    // Test database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Initialize EventManager
    $eventManager = new EventManager($conn);
    
    // Prepare filter parameters
    $filter = [
        'year' => $_GET['year'] ?? 'all',
        'category' => $_GET['category'] ?? 'all',
        'search' => $_GET['search'] ?? '',
        'status' => $_GET['status'] ?? 'all'
    ];

    // Get current page number
    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

    // Retrieve events
    try {
        $events_result = $eventManager->getEvents($filter, $current_page);

        // Enhanced logging
        error_log("Events Retrieval Details:");
        error_log("Total Events: " . count($events_result['events']));
        error_log("Pagination Details: " . json_encode($events_result['pagination']));
        
        // If no events, log more details
        if (empty($events_result['events'])) {
            error_log("NO EVENTS FOUND. Possible reasons:");
            error_log("1. No events in database");
            error_log("2. Filters may be too restrictive");
            error_log("Current Filters: " . json_encode($filter));
        }

    } catch (Exception $e) {
        // Comprehensive error logging
        error_log("EVENT RETRIEVAL ERROR: " . $e->getMessage());
        error_log("Error Trace: " . $e->getTraceAsString());
        
        $error_message = "Unable to retrieve events: " . $e->getMessage();
        
        // Set default empty events result
        $events_result = handleGlobalError($e);

        // Display error message to admin
        echo '<div class="alert alert-danger" role="alert">' . 
             htmlspecialchars($error_message) . 
             '</div>';
    }
    
    // Modify event retrieval to handle potential missing keys
    $events_result['events'] = array_map(function($event) {
        // Ensure all keys exist with default values
        $event += [
            'id' => null,
            'title' => '',
            'event_date' => '',
            'category' => 'Uncategorized',
            'location' => '',
            'status' => 'upcoming'
        ];
        return $event;
    }, $events_result['events'] ?? []);

    // Get available years and categories for filters
    $years = $eventManager->getYears();
    $categories = $eventManager->getCategories();

    // Prepare categories for dropdown
    $categories_dropdown = [
        'all' => 'All Categories'
    ];
    foreach ($categories as $category_id => $category_name) {
        $categories_dropdown[$category_id] = $category_name;
    }

    // Check for event creation success message
    if (isset($_SESSION['event_created_message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . 
             htmlspecialchars($_SESSION['event_created_message']) . 
             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
             </div>';
        
        // Log the success message
        error_log("Event Creation Success: " . $_SESSION['event_created_message']);
        
        // Clear the message
        unset($_SESSION['event_created_message']);
    }

    // Diagnostic output for page
    echo "<!-- Diagnostic Information\n";
    echo "Filter: " . json_encode($filter) . "\n";
    echo "Current Page: " . $current_page . "\n";
    echo "Total Events: " . count($events_result['events']) . "\n";
    echo "Pagination: " . json_encode($events_result['pagination']) . "\n";
    echo "-->";

} catch (Exception $global_error) {
    // Global error handling
    $events_result = handleGlobalError($global_error);
    
    // Display global error
    echo "<!-- Global Error: " . htmlspecialchars($global_error->getMessage()) . " -->";
}

// Add comprehensive error logging
try {
    // Direct query to check events
    $debug_query = "SELECT * FROM events LIMIT 10";
    
    if ($conn instanceof PDO) {
        $debug_stmt = $conn->prepare($debug_query);
        $debug_stmt->execute();
        $debug_events = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($conn instanceof mysqli) {
        $debug_stmt = $conn->prepare($debug_query);
        $debug_stmt->execute();
        $debug_result = $debug_stmt->get_result();
        $debug_events = $debug_result->fetch_all(MYSQLI_ASSOC);
        $debug_stmt->close();
    } else {
        throw new Exception("Unsupported database connection type");
    }
    
    // Log events for debugging
    error_log("DEBUG: Found " . count($debug_events) . " events");
    error_log("DEBUG Events: " . json_encode($debug_events, JSON_PRETTY_PRINT));
    
} catch (Exception $e) {
    error_log("CRITICAL DATABASE ERROR: " . $e->getMessage());
    echo "<!-- DATABASE ERROR: " . htmlspecialchars($e->getMessage()) . " -->";
}

// Test direct query
$test_query = "SELECT COUNT(*) as count FROM events";
    
if ($conn instanceof PDO) {
    $stmt = $conn->prepare($test_query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($conn instanceof mysqli) {
    $stmt = $conn->prepare($test_query);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $result = ['count' => $count];
    $stmt->close();
} else {
    throw new Exception("Unsupported database connection type");
}
    
if ($result) {
    $count = $result['count'];
    echo "<!-- Found {$count} events in database -->";
} else {
    echo "<!-- Failed to query events -->";
}

// Test EventManager
$eventManager = new EventManager($conn);
    
// Diagnostic logging
$diagnostic_info = $eventManager->diagnosticEventInfo();
echo "<!-- Diagnostic Info: " . htmlspecialchars(json_encode($diagnostic_info)) . " -->";

// Prepare filter parameters
$filter = [
    'year' => $_GET['year'] ?? 'all',
    'category' => $_GET['category'] ?? 'all',
    'search' => $_GET['search'] ?? '',
    'status' => $_GET['status'] ?? 'all'
];

// Get current page number
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Comprehensive error handling for events retrieval
try {
    $events_result = $eventManager->getEvents($filter, $current_page);

    // Enhanced logging
    error_log("Events Retrieval Details:");
    error_log("Total Events: " . count($events_result['events']));
    error_log("Pagination Details: " . json_encode($events_result['pagination']));
    
    // If no events, log more details
    if (empty($events_result['events'])) {
        error_log("NO EVENTS FOUND. Possible reasons:");
        error_log("1. No events in database");
        error_log("2. Filters may be too restrictive");
        error_log("Current Filters: " . json_encode($filter));
    }

} catch (Exception $e) {
    // Comprehensive error logging
    error_log("EVENT RETRIEVAL ERROR: " . $e->getMessage());
    error_log("Error Trace: " . $e->getTraceAsString());
    
    $error_message = "Unable to retrieve events: " . $e->getMessage();
    
    // Set default empty events result
    $events_result = [
        'events' => [],
        'pagination' => [
            'total_records' => 0,
            'total_pages' => 1,
            'current_page' => 1,
            'items_per_page' => 12,
            'prev_page' => null,
            'next_page' => null
        ]
    ];

    // Display error message to admin
    echo '<div class="alert alert-danger" role="alert">' . 
         htmlspecialchars($error_message) . 
         '</div>';
}

// Modify event retrieval to handle potential missing keys
$events_result['events'] = array_map(function($event) {
    // Ensure all keys exist with default values
    $event += [
        'id' => null,
        'title' => '',
        'event_date' => '',
        'category' => 'Uncategorized',
        'location' => '',
        'status' => 'upcoming'
    ];
    return $event;
}, $events_result['events'] ?? []);

// Get available years and categories for filters
$years = $eventManager->getYears();
$categories = $eventManager->getCategories();

// Prepare categories for dropdown
$categories_dropdown = [
    'all' => 'All Categories'
];
foreach ($categories as $category_id => $category_name) {
    $categories_dropdown[$category_id] = $category_name;
}

// Check for event creation success message
if (isset($_SESSION['event_created_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . 
         htmlspecialchars($_SESSION['event_created_message']) . 
         '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>';
    
    // Log the success message
    error_log("Event Creation Success: " . $_SESSION['event_created_message']);
    
    // Clear the message
    unset($_SESSION['event_created_message']);
}

// Debug: Log all session variables
error_log("Session Variables: " . print_r($_SESSION, true));

// Check for created parameter
if (isset($_GET['created']) && $_GET['created'] == '1') {
    // Additional logging for created parameter
    error_log("Accessed events page after event creation");
    
    // Attempt to retrieve the most recently created event
    $recent_event_query = "
        SELECT id, title, event_date, category_id 
        FROM events 
        ORDER BY created_at DESC 
        LIMIT 1
    ";
    
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare($recent_event_query);
        $stmt->execute();
        $recent_event = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($conn instanceof mysqli) {
        $stmt = $conn->prepare($recent_event_query);
        $stmt->execute();
        $result = $stmt->get_result();
        $recent_event = $result->fetch_assoc();
        $stmt->close();
    } else {
        throw new Exception("Unsupported database connection type");
    }
    
    if ($recent_event) {
        error_log("Most Recent Event Details: " . json_encode($recent_event));
    } else {
        error_log("No recent events found or query failed");
    }
}

// Debug Information
error_log("DEBUG: Events Page Variables");
error_log("Filter: " . json_encode($filter));
error_log("Current Page: " . $current_page);
error_log("Events Result: " . json_encode($events_result));
error_log("Database Connection: " . ($conn ? "Established" : "Failed"));
    
// Diagnostic output for page
echo "<!-- Diagnostic Information\n";
echo "Filter: " . json_encode($filter) . "\n";
echo "Current Page: " . $current_page . "\n";
echo "Total Events: " . count($events_result['events']) . "\n";
echo "Pagination: " . json_encode($events_result['pagination']) . "\n";
echo "-->";

// Comprehensive error handling for events retrieval
try {
    $events_result = $eventManager->getEvents($filter, $current_page);
} catch (Exception $e) {
    echo "<!-- Error: " . htmlspecialchars($e->getMessage()) . " -->";
    echo "<!-- Error Trace: " . htmlspecialchars($e->getTraceAsString()) . " -->";
    error_log("Events page error: " . $e->getMessage());
    error_log("Error Trace: " . $e->getTraceAsString());
    
    // Comprehensive error logging
    logError("Critical Error: " . $e->getMessage());
    logError("Error Trace: " . $e->getTraceAsString());
    
    // Set error message for user
    $_SESSION['error_message'] = "Unable to retrieve events: " . $e->getMessage();
    $events_result = ['events' => [], 'pagination' => []];
    $years = [];
    $categories = [];
}

// Add view parameter handling
$view = $_GET['view'] ?? 'upcoming';
$filter['status'] = $view === 'past' ? 'past' : 'upcoming';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - <?php echo SITE_NAME; ?></title>
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
                    <h2>Events</h2>
                    <div>
                        <button id="bulkDeleteBtn" class="btn btn-danger me-2" style="display: none;">
                            <i class="fas fa-trash me-2"></i>Delete Selected
                        </button>
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

                <?php if (empty($events_result['events'])): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No events found. Create your first event!
                    </div>
                <?php else: ?>
                    <!-- Event Tabs -->
                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (!isset($_GET['view']) || $_GET['view'] == 'upcoming') ? 'active' : ''; ?>" 
                               href="?view=upcoming">
                                <i class="fas fa-calendar-check me-2"></i>Upcoming Events
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (isset($_GET['view']) && $_GET['view'] == 'past') ? 'active' : ''; ?>" 
                               href="?view=past">
                                <i class="fas fa-history me-2"></i>Past Events
                            </a>
                        </li>
                    </ul>

                    <!-- Events Table -->
                    <div class="card event-card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="selectAllCheckbox" class="form-check-input">
                                            </th>
                                            <th>Title</th>
                                            <th>Date</th>
                                            <th>Category</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($events_result['events'] as $event): ?>
                                        <tr>
                                            <td>
                                                <input 
                                                    type="checkbox" 
                                                    class="form-check-input event-checkbox" 
                                                    value="<?php echo htmlspecialchars($event['id']); ?>"
                                                >
                                            </td>
                                            <td><?php echo htmlspecialchars($event['title']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($event['event_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($event['category'] ?? 'Uncategorized'); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="view.php?id=<?php echo $event['id']; ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye me-1"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $event['id']; ?>" 
                                                       class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit me-1"></i>
                                                    </a>
                                                    <?php 
                                                    // Modify delete link to include CSRF token
                                                    $delete_link = "delete.php?id={$event['id']}&" . CSRF_TOKEN_NAME . "=" . generate_csrf_token();
                                                    ?>
                                                    <a href="<?php echo $delete_link; ?>" 
                                                       class="btn btn-sm btn-danger delete-event" data-id="<?php echo $event['id']; ?>">
                                                        <i class="fas fa-trash me-1"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <?php if ($events_result['pagination']['total_pages'] > 1): ?>
                    <nav aria-label="Event pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($events_result['pagination']['prev_page']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?view=<?php echo $_GET['view'] ?? 'upcoming'; ?>&page=<?php echo $events_result['pagination']['prev_page']; ?>">
                                        <i class="fas fa-chevron-left me-2"></i>Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $events_result['pagination']['total_pages']; $i++): ?>
                                <li class="page-item <?php echo $i == $events_result['pagination']['current_page'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="?view=<?php echo $_GET['view'] ?? 'upcoming'; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($events_result['pagination']['next_page']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?view=<?php echo $_GET['view'] ?? 'upcoming'; ?>&page=<?php echo $events_result['pagination']['next_page']; ?>">
                                        Next<i class="fas fa-chevron-right ms-2"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Optimize bulk delete functionality
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.event-checkbox');
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const csrfToken = '<?php echo generate_csrf_token(); ?>';

            // Debounce function to prevent multiple rapid clicks
            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            // Track selected checkboxes
            function updateBulkDeleteButton() {
                const selectedCheckboxes = document.querySelectorAll('.event-checkbox:checked');
                
                if (selectedCheckboxes.length > 0) {
                    bulkDeleteBtn.style.display = 'inline-block';
                    bulkDeleteBtn.textContent = `Delete ${selectedCheckboxes.length} Selected`;
                } else {
                    bulkDeleteBtn.style.display = 'none';
                }
            }

            // Add event listeners to checkboxes
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkDeleteButton);
            });

            // Select all checkbox functionality
            selectAllCheckbox.addEventListener('change', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updateBulkDeleteButton();
            });

            // Optimized bulk delete handler
            const bulkDeleteHandler = debounce(function() {
                const selectedCheckboxes = document.querySelectorAll('.event-checkbox:checked');
                const selectedEventIds = Array.from(selectedCheckboxes).map(cb => cb.value);

                if (selectedEventIds.length === 0) {
                    alert('Please select events to delete.');
                    return;
                }

                if (!confirm(`Are you sure you want to delete ${selectedEventIds.length} event(s)?`)) {
                    return;
                }

                // Disable button during submission to prevent multiple clicks
                bulkDeleteBtn.disabled = true;
                bulkDeleteBtn.textContent = 'Deleting...';

                // Create form for bulk delete
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'admin/events/bulk_delete.php';

                // Add CSRF token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '<?php echo CSRF_TOKEN_NAME; ?>';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);

                // Add selected event IDs
                selectedEventIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_events[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            }, 300);

            // Add click event with optimized handler
            bulkDeleteBtn.addEventListener('click', bulkDeleteHandler);
        });

        // Individual event delete
        document.querySelectorAll('.delete-event').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default link behavior
                const eventId = this.getAttribute('data-id');
                const eventRow = this.closest('tr');
                
                // Safely get CSRF token
                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '<?php echo generate_csrf_token(); ?>';
                
                if (confirm('Are you sure you want to delete this event?')) {
                    fetch(`delete.php?id=${eventId}&<?php echo CSRF_TOKEN_NAME; ?>=${csrfToken}`, {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        // Check if response is OK and is JSON
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Remove the event row from the table
                            eventRow.remove();
                            alert('Event deleted successfully');
                        } else {
                            // Log debug info to console
                            console.error('Delete failed:', data);
                            alert('Failed to delete event: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the event. Please check the console for details.');
                    });
                }
            });
        });
    </script>
</body>
</html>

<?php 
// Close database connection
if ($conn instanceof PDO) {
    $conn = null;
} elseif ($conn instanceof mysqli) {
    $conn->close();
}
?>
