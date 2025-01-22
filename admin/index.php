<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Check login status
checkLogin();

// Get database connection
$conn = Database::getInstance()->getConnection();
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

// Initialize stats array
$stats = [
    'events' => 0,
    'donations' => 0,
    'team_members' => 0,
    'partners' => 0
];

// Function to safely get table count
function getTableCount($conn, $table) {
    try {
        // Sanitize table name (basic protection against SQL injection)
        $sanitized_table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        if ($conn instanceof PDO) {
            // PDO method
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM " . $conn->quote($sanitized_table));
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['count'] : 0;
        } elseif ($conn instanceof mysqli) {
            // MySQLi method
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM `" . $sanitized_table . "`");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row ? $row['count'] : 0;
        } else {
            error_log("Unsupported database connection type");
            return 0;
        }
    } catch (Exception $e) {
        error_log("Error counting table {$table}: " . $e->getMessage());
        return 0;
    }
}

// Function to safely get recent items
function getRecentItems($conn, $table, $limit = 5) {
    try {
        // Sanitize table name (basic protection against SQL injection)
        $sanitized_table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $sanitized_limit = intval($limit);
        
        if ($conn instanceof PDO) {
            // PDO method
            $stmt = $conn->prepare("SELECT * FROM " . $conn->quote($sanitized_table) . " ORDER BY created_at DESC LIMIT :limit");
            $stmt->bindParam(':limit', $sanitized_limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($conn instanceof mysqli) {
            // MySQLi method
            $stmt = $conn->prepare("SELECT * FROM `" . $sanitized_table . "` ORDER BY created_at DESC LIMIT ?");
            $stmt->bind_param('i', $sanitized_limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $items = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $items;
        } else {
            error_log("Unsupported database connection type");
            return [];
        }
    } catch (Exception $e) {
        error_log("Error fetching recent items from {$table}: " . $e->getMessage());
        return [];
    }
}

// Get stats safely
$stats['events'] = getTableCount($conn, 'events');
$stats['donations'] = getTableCount($conn, 'donations');
$stats['team_members'] = getTableCount($conn, 'team_members');
$stats['partners'] = getTableCount($conn, 'partners');

// Get recent items safely
$recentEvents = getRecentItems($conn, 'events');
$recentDonations = getRecentItems($conn, 'donations');
$recentTeam = getRecentItems($conn, 'team_members');
$recentPartners = getRecentItems($conn, 'partners');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        }
        .sidebar .nav-link:hover {
            color: white;
        }
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        .main-content {
            padding: 20px;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-card h3 {
            color: var(--primary-color);
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
                        <a class="nav-link active" href="/admin">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/events">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/team">Team</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/donations">Donations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/partners">Partners</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/logout">Logout</a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard</h2>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                </div>

                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-calendar-alt fa-3x mb-3 text-primary"></i>
                            <h3><?php echo $stats['events']; ?></h3>
                            <p>Total Events</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-heart fa-3x mb-3 text-danger"></i>
                            <h3><?php echo $stats['donations']; ?></h3>
                            <p>Total Donations</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-users fa-3x mb-3 text-success"></i>
                            <h3><?php echo $stats['team_members']; ?></h3>
                            <p>Team Members</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-handshake fa-3x mb-3 text-warning"></i>
                            <h3><?php echo $stats['partners']; ?></h3>
                            <p>Partners</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h4>Recent Events</h4>
                        <ul class="list-group">
                            <?php foreach ($recentEvents as $event): ?>
                                <li class="list-group-item">
                                    <?php echo htmlspecialchars($event['title']); ?>
                                    <small class="text-muted float-end"><?php echo htmlspecialchars($event['event_date']); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h4>Recent Donations</h4>
                        <ul class="list-group">
                            <?php foreach ($recentDonations as $donation): ?>
                                <li class="list-group-item">
                                    <?php echo htmlspecialchars($donation['donor_name'] ?? 'Anonymous'); ?>
                                    <small class="text-muted float-end"><?php echo htmlspecialchars($donation['amount'] ?? ''); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>