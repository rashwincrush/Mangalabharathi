<?php
// Ensure only logged-in admins can access
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . ADMIN_URL . '/login.php');
    exit();
}
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'index.php') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo ADMIN_URL; ?>/">
                    <span data-feather="home"></span>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'events') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo ADMIN_URL; ?>/events">
                    <span data-feather="calendar"></span>
                    Events
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'donations') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo ADMIN_URL; ?>/donations">
                    <span data-feather="dollar-sign"></span>
                    Donations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'team') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo ADMIN_URL; ?>/team">
                    <span data-feather="users"></span>
                    Team
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'partners') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo ADMIN_URL; ?>/partners">
                    <span data-feather="briefcase"></span>
                    Partners
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo ADMIN_URL; ?>/logout.php">
                    <span data-feather="log-out"></span>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>
