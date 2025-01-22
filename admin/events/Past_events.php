<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/EventManager.php';
require_once '../includes/auth.php';

// Check login
checkLogin();

// Get database connection
$conn = Database::getInstance()->getConnection();

// Initialize EventManager
$eventManager = new EventManager($conn);

// Prepare filter parameters for past events
$filter = [
    'year' => $_GET['year'] ?? 'all',
    'category' => $_GET['category'] ?? 'all',
    'search' => $_GET['search'] ?? '',
    'status' => 'past'  // Force past events only
];

// Get current page number
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

try {
    $events_result = $eventManager->getEvents($filter, $current_page);
    $categories = $eventManager->getCategories();
    $years = $eventManager->getYears();
} catch (Exception $e) {
    error_log("Error retrieving past events: " . $e->getMessage());
    $events_result = [
        'events' => [],
        'pagination' => [
            'total_records' => 0,
            'total_pages' => 1,
            'current_page' => 1,
            'items_per_page' => 12
        ]
    ];
    $categories = [];
    $years = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Past Events - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/admin'; ?>/assets/css/variables.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: var(--color-background);
            font-family: 'Inter', sans-serif;
        }
        .sidebar {
            background-color: var(--color-sidebar-bg);
            min-height: 100vh;
            color: var(--color-sidebar-text);
        }
        .sidebar .nav-link {
            color: var(--color-sidebar-text);
            padding: 0.5rem 1rem;
            margin: 0.2rem 0;
            transition: all var(--transition-speed) ease;
        }
        .sidebar .nav-link:hover {
            color: var(--color-sidebar-active);
            background-color: rgba(245, 152, 36, 0.1);
        }
        .sidebar .nav-link.active {
            background-color: var(--color-primary);
            color: white;
        }
        .main-content {
            background-color: var(--color-background-light);
            padding: var(--spacing-large);
        }
        .card {
            border-radius: var(--border-radius-large);
            box-shadow: var(--card-box-shadow);
            margin-bottom: var(--spacing-large);
        }
        .table {
            border-radius: var(--border-radius-medium);
            overflow: hidden;
        }
        .table thead {
            background-color: var(--color-background-muted);
        }
        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
            transition: all var(--transition-speed) ease;
        }
        .btn-primary:hover {
            background-color: var(--color-primary-dark);
            border-color: var(--color-primary-dark);
        }
        .pagination .page-item.active .page-link {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
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
                        <h2 class="text-primary mb-0">Past Events</h2>
                    </div>
                    <div>
                        <div class="btn-group me-2">
                            <a href="/admin/events/Upcoming_events.php" class="btn btn-secondary">
                                <i class="fas fa-calendar-check me-2"></i>Upcoming Events
                            </a>
                            <a href="/admin/events/Past_events.php" class="btn btn-primary">
                                <i class="fas fa-history me-2"></i>Past Events
                            </a>
                        </div>
                        <a href="/admin/events/create.php" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Create Event
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <!-- Filter Section -->
                        <div class="mb-4">
                            <form method="get" class="row g-3">
                                <div class="col-md-3">
                                    <select name="year" class="form-select">
                                        <option value="all">All Years</option>
                                        <?php foreach ($years as $year): ?>
                                            <option value="<?php echo $year; ?>" <?php echo ($filter['year'] == $year) ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="category" class="form-select">
                                        <option value="all">All Categories</option>
                                        <?php foreach ($categories as $id => $name): ?>
                                            <option value="<?php echo $id; ?>" <?php echo ($filter['category'] == $id) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="search" class="form-control" placeholder="Search events..." 
                                           value="<?php echo htmlspecialchars($filter['search']); ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </form>
                        </div>

                        <!-- Events Table -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAllCheckbox">
                                        </th>
                                        <th>Title</th>
                                        <th>Date</th>
                                        <th>Location</th>
                                        <th>People Helped</th>
                                        <th>Volunteers</th>
                                        <th>Impact Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($events_result['events'])): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No past events found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($events_result['events'] as $event): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="event-checkbox" name="selected_events[]" 
                                                           value="<?php echo $event['id']; ?>">
                                                </td>
                                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                                                <td><?php echo date('d M Y', strtotime($event['event_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($event['location'] ?? ''); ?></td>
                                                <td><?php echo number_format($event['people_helped'] ?? 0); ?></td>
                                                <td><?php echo number_format($event['actual_volunteers'] ?? 0); ?></td>
                                                <td>
                                                    <?php 
                                                    $impact = $event['impact_description'] ?? '';
                                                    echo strlen($impact) > 50 ? substr($impact, 0, 47) . '...' : $impact;
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="view.php?id=<?php echo $event['id']; ?>" 
                                                           class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit.php?id=<?php echo $event['id']; ?>" 
                                                           class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-danger delete-event" 
                                                                data-id="<?php echo $event['id']; ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($events_result['pagination']['total_pages'] > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $events_result['pagination']['total_pages']; $i++): ?>
                                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&year=<?php echo $filter['year']; ?>&category=<?php echo $filter['category']; ?>&search=<?php echo urlencode($filter['search']); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-3 text-center">
                    <a href="/admin/events/" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Events
                    </a>
                    <button id="bulkDeleteBtn" class="btn btn-danger ms-2" style="display: none;">Delete Selected</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.event-checkbox');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

        // Select all checkbox functionality
        selectAllCheckbox.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateBulkDeleteButton();
        });

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

        // Bulk delete functionality
        bulkDeleteBtn.addEventListener('click', function() {
            const selectedCheckboxes = document.querySelectorAll('.event-checkbox:checked');
            const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);

            if (selectedIds.length > 0) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: `You want to delete ${selectedIds.length} events?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Send AJAX request to delete events
                        fetch('delete.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ event_ids: selectedIds })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire(
                                    'Deleted!',
                                    `${selectedIds.length} events have been deleted.`,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    data.message || 'Failed to delete events.',
                                    'error'
                                );
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire(
                                'Error!',
                                'An unexpected error occurred.',
                                'error'
                            );
                        });
                    }
                });
            }
        });
    });
    </script>
</body>
</html>