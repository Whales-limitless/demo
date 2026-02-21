<?php
// Shared admin layout - include in all admin pages
// Usage: $currentPage = 'dashboard'; include('nav.php');
// This file outputs: layout CSS overrides + topbar + sidebar + toggle JS
?>
<style>
/* ===== TOPBAR ===== */
.admin-topbar {
    position: fixed !important;
    top: 0; left: 0; right: 0;
    height: 60px;
    background: var(--primary, #C8102E);
    color: #fff;
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 1010;
    box-shadow: 0 2px 12px rgba(200,16,46,0.3);
}
.admin-topbar .topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.admin-topbar .brand {
    font-family: 'Outfit', sans-serif;
    font-size: 18px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}
.admin-topbar .brand i { font-size: 20px; }
.admin-topbar .sidebar-toggle {
    background: rgba(255,255,255,0.15);
    border: none;
    color: #fff;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    display: none;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}
.admin-topbar .sidebar-toggle:hover { background: rgba(255,255,255,0.25); }
.admin-topbar .right-section {
    display: flex;
    align-items: center;
    gap: 16px;
}
.admin-topbar .user-info { font-size: 13px; opacity: 0.9; }
.admin-topbar .btn-logout {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border: none;
    padding: 7px 16px;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s;
}
.admin-topbar .btn-logout:hover { background: rgba(255,255,255,0.25); color: #fff; }
/* Hide old nav-links if still present */
.admin-topbar .nav-links { display: none !important; }

/* ===== SIDEBAR ===== */
.admin-sidebar {
    position: fixed;
    top: 60px;
    left: 0;
    width: 250px;
    bottom: 0;
    background: #1a1a2e;
    z-index: 1005;
    overflow-y: auto;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.1) transparent;
}
.admin-sidebar::-webkit-scrollbar { width: 4px; }
.admin-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
.sidebar-nav { padding: 12px 0; }
.sidebar-section { margin-bottom: 4px; }
.sidebar-section-title {
    padding: 12px 20px 6px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(255,255,255,0.35);
    font-family: 'DM Sans', sans-serif;
}
.sidebar-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 20px;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    font-family: 'DM Sans', sans-serif;
    transition: all 0.2s;
    border-left: 3px solid transparent;
}
.sidebar-link:hover {
    background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.9);
}
.sidebar-link.active {
    background: rgba(200,16,46,0.15);
    color: #fff;
    border-left-color: var(--primary, #C8102E);
    font-weight: 600;
}
.sidebar-link i {
    width: 20px;
    text-align: center;
    font-size: 14px;
}
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.4);
    z-index: 1004;
}

/* ===== LAYOUT OVERRIDES ===== */
.page-content, .dashboard-content {
    margin-left: 250px !important;
    padding-top: 80px !important;
    max-width: none !important;
    padding-left: 24px;
    padding-right: 24px;
    padding-bottom: 40px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
    .admin-topbar .sidebar-toggle { display: flex; }
    .admin-sidebar {
        transform: translateX(-100%);
    }
    .admin-sidebar.open {
        transform: translateX(0);
    }
    .sidebar-overlay.open { display: block; }
    .page-content, .dashboard-content {
        margin-left: 0 !important;
        padding-top: 76px !important;
        padding-left: 16px;
        padding-right: 16px;
    }
}
@media (max-width: 768px) {
    .admin-topbar { padding: 0 16px; }
    .admin-topbar .brand { font-size: 16px; }
}
</style>

<?php include('topbar.php'); ?>
<?php include('sidebar.php'); ?>

<script>
function toggleSidebar() {
    document.getElementById('adminSidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
}
</script>
