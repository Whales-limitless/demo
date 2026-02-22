<?php
// Standalone sidebar - included by nav.php
// Requires: $currentPage to be set by the including page
$sidebarSections = [
    'MAIN' => [
        ['page' => 'dashboard',      'href' => 'dashboard.php',        'icon' => 'fas fa-list-alt',             'label' => 'Orders'],
        ['page' => 'product',        'href' => 'product.php',          'icon' => 'fas fa-boxes-stacked',        'label' => 'Products'],
        ['page' => 'product_trend',  'href' => 'product_trend.php',    'icon' => 'fas fa-chart-line',           'label' => 'Product Trends'],
    ],
    'PURCHASING' => [
        ['page' => 'supplier',    'href' => 'supplier.php',         'icon' => 'fas fa-truck',                'label' => 'Suppliers'],
        ['page' => 'po',          'href' => 'po.php',               'icon' => 'fas fa-file-invoice',         'label' => 'Purchase Orders'],
        ['page' => 'grn',         'href' => 'grn.php',              'icon' => 'fas fa-dolly',                'label' => 'Receiving'],
    ],
    'INVENTORY' => [
        ['page' => 'rack',        'href' => 'rack.php',             'icon' => 'fas fa-warehouse',            'label' => 'Racks'],
        ['page' => 'stock_take',  'href' => 'stock_take.php',       'icon' => 'fas fa-clipboard-check',      'label' => 'Stock Take'],
        ['page' => 'stock_loss',  'href' => 'stock_loss.php',       'icon' => 'fas fa-exclamation-triangle', 'label' => 'Stock Loss'],
    ],
    'ADMIN' => [
        ['page' => 'user',        'href' => 'user.php',             'icon' => 'fas fa-users',                'label' => 'Users'],
    ],
];
?>
<aside class="admin-sidebar" id="adminSidebar">
    <nav class="sidebar-nav">
        <?php foreach ($sidebarSections as $section => $items): ?>
        <div class="sidebar-section">
            <div class="sidebar-section-title"><?php echo $section; ?></div>
            <?php foreach ($items as $item): ?>
            <a href="<?php echo $item['href']; ?>" class="sidebar-link<?php echo (isset($currentPage) && $currentPage === $item['page']) ? ' active' : ''; ?>">
                <i class="<?php echo $item['icon']; ?>"></i>
                <span><?php echo $item['label']; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </nav>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar();"></div>
