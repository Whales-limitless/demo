<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

// Count pending stock take sessions (DRAFT status) for badge
$stBadge = 0;
$stResult = $connect->query("SELECT COUNT(*) AS cnt FROM `stock_take` WHERE `status` = 'DRAFT'");
if ($stResult && $row = $stResult->fetch_assoc()) {
    $stBadge = intval($row['cnt']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="components.css">
    <style>
        :root {
            --primary: #C8102E;
            --primary-dark: #a00d24;
            --surface: #ffffff;
            --bg: #f3f4f6;
            --text: #1a1a1a;
            --text-muted: #6b7280;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            padding-bottom: 80px;
            min-height: 100vh;
        }

        h1, h2, h3 { font-family: 'Outfit', sans-serif; }

        .page-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--primary);
            color: #fff;
            padding: 0 16px;
            height: 56px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 12px rgba(200, 16, 46, 0.3);
        }

        .page-title {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            font-weight: 600;
        }

        .main-content {
            max-width: 700px;
            margin: 0 auto;
            padding: 16px;
        }

        /* User Profile Card */
        .profile-card {
            background: var(--surface);
            border-radius: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04);
            padding: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .profile-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .profile-info h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .profile-info p {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Menu List */
        .menu-section {
            margin-bottom: 20px;
        }

        .menu-section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            padding: 0 4px;
            margin-bottom: 8px;
        }

        .menu-list {
            background: var(--surface);
            border-radius: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            text-decoration: none;
            color: var(--text);
            transition: background 0.15s;
            border-bottom: 1px solid #f3f4f6;
        }

        .menu-item:last-child {
            border-bottom: none;
        }

        .menu-item:hover, .menu-item:active {
            background: #f9fafb;
        }

        .menu-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .menu-icon svg {
            width: 22px;
            height: 22px;
        }

        .menu-icon.blue { background: #dbeafe; color: #2563eb; }
        .menu-icon.orange { background: #fef3c7; color: #d97706; }
        .menu-icon.red { background: #fef2f2; color: #dc2626; }

        .menu-text {
            flex: 1;
            min-width: 0;
        }

        .menu-text h3 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .menu-text p {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.3;
        }

        .menu-badge {
            background: var(--primary);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 2px 9px;
            border-radius: 20px;
            flex-shrink: 0;
        }

        .menu-arrow {
            color: #d1d5db;
            flex-shrink: 0;
        }

        .menu-arrow svg {
            width: 18px;
            height: 18px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <span class="page-title">Account</span>
    </header>

    <div class="main-content">

        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></h2>
                <p>Staff<?php echo !empty($_SESSION['user_outlet']) ? ' &middot; ' . htmlspecialchars($_SESSION['user_outlet']) : ''; ?></p>
            </div>
        </div>

        <!-- Inventory Section -->
        <div class="menu-section">
            <div class="menu-section-title">Inventory</div>
            <div class="menu-list">
                <a href="staff_stock_take.php" class="menu-item">
                    <div class="menu-icon blue">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                        </svg>
                    </div>
                    <div class="menu-text">
                        <h3>Stock Take</h3>
                        <p>Count and verify inventory</p>
                    </div>
                    <?php if ($stBadge > 0): ?>
                    <span class="menu-badge"><?php echo $stBadge; ?></span>
                    <?php endif; ?>
                    <div class="menu-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </div>
                </a>
                <a href="staff_stock_loss.php" class="menu-item">
                    <div class="menu-icon orange">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <div class="menu-text">
                        <h3>Stock Loss</h3>
                        <p>Report damaged or lost stock</p>
                    </div>
                    <div class="menu-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </div>
                </a>
            </div>
        </div>

        <!-- Delivery Section -->
        <div class="menu-section">
            <div class="menu-section-title">Delivery</div>
            <div class="menu-list">
                <a href="del_dashboard.php" class="menu-item">
                    <div class="menu-icon blue">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <rect x="1" y="3" width="15" height="13" rx="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                        </svg>
                    </div>
                    <div class="menu-text">
                        <h3>My Deliveries</h3>
                        <p>View assigned deliveries</p>
                    </div>
                    <div class="menu-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </div>
                </a>
                <a href="del_history.php" class="menu-item">
                    <div class="menu-icon orange">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="menu-text">
                        <h3>Delivery History</h3>
                        <p>View completed deliveries</p>
                    </div>
                    <div class="menu-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </div>
                </a>
                <a href="del_report.php" class="menu-item">
                    <div class="menu-icon blue">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                    </div>
                    <div class="menu-text">
                        <h3>Delivery Reports</h3>
                        <p>View your delivery reports</p>
                    </div>
                    <div class="menu-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </div>
                </a>
            </div>
        </div>

        <!-- Account Section -->
        <div class="menu-section">
            <div class="menu-section-title">Account</div>
            <div class="menu-list">
                <a href="logout.php" class="menu-item">
                    <div class="menu-icon red">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                        </svg>
                    </div>
                    <div class="menu-text">
                        <h3>Logout</h3>
                        <p>Sign out of your account</p>
                    </div>
                    <div class="menu-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </div>
                </a>
            </div>
        </div>

    </div>

    <?php include 'mobile-bottombar.php'; ?>
</body>
</html>
