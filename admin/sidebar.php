<?php
// Standalone sidebar - included by nav.php
// Requires: $currentPage to be set by the including page
$sidebarSections = [
    'MAIN' => [
        ['page' => 'dashboard',      'href' => 'dashboard.php',        'icon' => 'fas fa-list-alt',             'label' => 'Orders'],
        ['page' => 'cat_group',      'href' => 'cat_group.php',        'icon' => 'fas fa-layer-group',          'label' => 'Category Groups'],
        ['page' => 'product',        'href' => 'product.php',          'icon' => 'fas fa-boxes-stacked',        'label' => 'Products'],
        ['page' => 'product_bulk',  'href' => 'product_bulk.php',     'icon' => 'fas fa-pen-to-square',        'label' => 'Bulk Edit Products'],
        ['page' => 'product_trend',  'href' => 'product_trend.php',    'icon' => 'fas fa-chart-line',           'label' => 'Product Trends'],
    ],
    'PROCUREMENT' => [
        ['page' => 'supplier',    'href' => 'supplier.php',         'icon' => 'fas fa-truck',                'label' => 'Suppliers'],
        ['page' => 'quotation',   'href' => 'quotation.php',        'icon' => 'fas fa-file-signature',       'label' => 'Quotations'],
        ['page' => 'po',          'href' => 'po.php',               'icon' => 'fas fa-file-invoice',         'label' => 'Purchase Orders'],
        ['page' => 'grn',         'href' => 'grn.php',              'icon' => 'fas fa-dolly',                'label' => 'Goods Receiving'],
    ],
    'INVENTORY' => [
        ['page' => 'rack',        'href' => 'rack.php',             'icon' => 'fas fa-warehouse',            'label' => 'Racks'],
        ['page' => 'stock_take',  'href' => 'stock_take.php',       'icon' => 'fas fa-clipboard-check',      'label' => 'Stock Take'],
        ['page' => 'stock_loss',  'href' => 'stock_loss.php',       'icon' => 'fas fa-exclamation-triangle', 'label' => 'Stock Loss'],
    ],
    'DELIVERY' => [
        ['page' => 'del_dashboard', 'href' => 'del_dashboard.php', 'icon' => 'fas fa-truck',             'label' => 'Delivery Board'],
        ['page' => 'del_order',     'href' => 'del_order.php',     'icon' => 'fas fa-file-invoice',      'label' => 'Delivery Orders'],
        ['page' => 'del_assign',    'href' => 'del_assign.php',    'icon' => 'fas fa-user-check',        'label' => 'Assign Driver'],
        ['page' => 'del_customer',  'href' => 'del_customer.php',  'icon' => 'fas fa-address-book',      'label' => 'Customers'],
        ['page' => 'del_location',  'href' => 'del_location.php',  'icon' => 'fas fa-map-marker-alt',    'label' => 'Locations'],
        ['page' => 'inst_approval', 'href' => 'inst_approval.php', 'icon' => 'fas fa-clipboard-check',   'label' => 'Installation Approval'],
        ['page' => 'del_report',    'href' => 'del_report.php',    'icon' => 'fas fa-chart-bar',         'label' => 'Delivery User Reports'],
    ],
    'REPORTS' => [
        ['page' => 'report_stock_take',     'href' => 'report_stock_take.php',     'icon' => 'fas fa-clipboard-list', 'label' => 'Stock Take Report'],
        ['page' => 'report_stock_movement', 'href' => 'report_stock_movement.php', 'icon' => 'fas fa-exchange-alt',   'label' => 'Stock Movement'],
        ['page' => 'report_sales_date',     'href' => 'report_sales_date.php',     'icon' => 'fas fa-calendar-alt',   'label' => 'Sales by Date'],
        ['page' => 'report_sales_staff',    'href' => 'report_sales_staff.php',    'icon' => 'fas fa-user-tie',       'label' => 'Sales by Staff'],
        ['page' => 'report_sales_product',  'href' => 'report_sales_product.php',  'icon' => 'fas fa-box-open',       'label' => 'Sales by Product'],
        ['page' => 'report_sales_branch',   'href' => 'report_sales_branch.php',   'icon' => 'fas fa-store',          'label' => 'Sales by Branch'],
        ['page' => 'report_purchase',       'href' => 'report_purchase.php',       'icon' => 'fas fa-dolly',          'label' => 'Purchase History'],
        ['page' => 'report_negative_qoh',   'href' => 'report_negative_qoh.php',   'icon' => 'fas fa-exclamation-triangle', 'label' => 'Negative QOH'],
    ],
    'ADMIN' => [
        ['page' => 'user',             'href' => 'user.php',             'icon' => 'fas fa-users',     'label' => 'Users'],
        ['page' => 'company_setting',  'href' => 'company_setting.php',  'icon' => 'fas fa-building',  'label' => 'Company Setting'],
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
