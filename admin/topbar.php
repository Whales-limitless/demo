<?php
// Standalone topbar - included by nav.php
// Requires: $_SESSION['admin_name'] to be set
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');
?>
<div class="admin-topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle" onclick="toggleSidebar();" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <div class="brand">
            <i class="fas fa-tachometer-alt"></i>
            Admin Panel
        </div>
    </div>
    <div class="right-section">
        <span class="user-info d-none d-md-inline">
            <i class="fas fa-user-circle"></i> <?php echo $adminName; ?>
        </span>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
