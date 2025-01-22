<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Check login status
checkLogin();

// Get database connection
$conn = get_db_connection();

// Initialize stats array
$stats = [
    'events' => 0,
    'donations' => 0,
    'team_members' => 0,
    'partners' => 0
];

// Function to safely get table count
function getTableCount($conn, $table) {
    $table = $conn->real_escape_string($table);
    $result = $conn->query("SELECT COUNT(*) as count FROM {$table}");
    return $result ? $result->fetch_assoc()['count'] : 0;
}

// Get stats safely
$stats['events'] = getTableCount($conn, 'events');
$stats['donations'] = getTableCount($conn, 'donations');
$stats['team_members'] = getTableCount($conn, 'team_members');
$stats['partners'] = getTableCount($conn, 'partners');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="<?php echo ADMIN_URL; ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="events/create.php" class="btn btn-sm btn-outline-secondary">Add New Event</a>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Events</h5>
                                <p class="card-text display-6"><?php echo $stats['events']; ?></p>
                                <a href="/admin/events/" class="btn btn-primary btn-sm">View Events</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Donations</h5>
                                <p class="card-text display-6"><?php echo $stats['donations']; ?></p>
                                <a href="donations/" class="btn btn-primary btn-sm">View Donations</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Team Members</h5>
                                <p class="card-text display-6"><?php echo $stats['team_members']; ?></p>
                                <a href="team/" class="btn btn-primary btn-sm">View Team</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Partners</h5>
                                <p class="card-text display-6"><?php echo $stats['partners']; ?></p>
                                <a href="partners/" class="btn btn-primary btn-sm">View Partners</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
