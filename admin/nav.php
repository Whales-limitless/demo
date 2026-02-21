<?php
// Shared admin navigation - include in all admin pages
// Usage: $currentPage = 'dashboard'; include('nav.php');
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');

$navItems = [
    ['page' => 'dashboard',   'href' => 'dashboard.php',        'icon' => 'fas fa-list-alt',             'label' => 'Orders'],
    ['page' => 'user',        'href' => 'user.php',             'icon' => 'fas fa-users',                'label' => 'Users'],
    ['page' => 'product',     'href' => 'product.php',          'icon' => 'fas fa-boxes-stacked',        'label' => 'Products'],
    ['page' => 'supplier',    'href' => 'supplier.php',         'icon' => 'fas fa-truck',                'label' => 'Suppliers'],
    ['page' => 'po',          'href' => 'po.php',               'icon' => 'fas fa-file-invoice',         'label' => 'PO'],
    ['page' => 'grn',         'href' => 'grn.php',              'icon' => 'fas fa-dolly',                'label' => 'Receiving'],
    ['page' => 'rack',        'href' => 'rack.php',             'icon' => 'fas fa-warehouse',            'label' => 'Racks'],
    ['page' => 'stock_take',  'href' => 'stock_take.php',       'icon' => 'fas fa-clipboard-check',      'label' => 'Stock Take'],
    ['page' => 'stock_loss',  'href' => 'stock_loss.php',       'icon' => 'fas fa-exclamation-triangle',  'label' => 'Stock Loss'],
];
?>
<div class="admin-topbar">
    <div class="brand">
        <i class="fas fa-tachometer-alt"></i>
        Admin Panel
    </div>
    <div class="nav-links">
        <?php foreach ($navItems as $item): ?>
        <a href="<?php echo $item['href']; ?>"<?php echo (isset($currentPage) && $currentPage === $item['page']) ? ' class="active"' : ''; ?>><i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['label']; ?></a>
        <?php endforeach; ?>
    </div>
    <div class="right-section">
        <span class="user-info d-none d-md-inline">
            <i class="fas fa-user-circle"></i> <?php echo $adminName; ?>
        </span>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
