<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
require_once 'dbconnection.php';

$userCode = $_SESSION['user_code'] ?? '';
$userName = $_SESSION['user_name'] ?? 'User';

// Fetch purchase history (grouped by SALNUM)
$purchases = [];
$purchaseQuery = "SELECT SALNUM, SDATE, TTIME, COUNT(*) AS item_count, SUM(QTY) AS total_qty, SUM(AMOUNT) AS total_amount, STATUS
    FROM orderlist
    WHERE ACCODE = '" . mysqli_real_escape_string($connect, $userCode) . "' AND PTYPE = 'PURCHASE'
    GROUP BY SALNUM, SDATE, TTIME, STATUS
    ORDER BY SDATE DESC, TTIME DESC
    LIMIT 50";
$pResult = mysqli_query($connect, $purchaseQuery);
if ($pResult) {
    while ($row = mysqli_fetch_assoc($pResult)) {
        $purchases[] = $row;
    }
}

// Fetch stock in history (grouped by SALNUM)
$stockins = [];
$stockinQuery = "SELECT SALNUM, SDATE, TTIME, COUNT(*) AS item_count, SUM(QTY) AS total_qty, STATUS
    FROM orderlist
    WHERE ACCODE = '" . mysqli_real_escape_string($connect, $userCode) . "' AND PTYPE = 'STOCKIN'
    GROUP BY SALNUM, SDATE, TTIME, STATUS
    ORDER BY SDATE DESC, TTIME DESC
    LIMIT 50";
$sResult = mysqli_query($connect, $stockinQuery);
if ($sResult) {
    while ($row = mysqli_fetch_assoc($sResult)) {
        $stockins[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account - Inventory</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="components.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --primary: #C8102E;
  --primary-dark: #a00d24;
  --surface: #ffffff;
  --bg: #f3f4f6;
  --text: #1a1a1a;
  --text-muted: #6b7280;
  --green: #16a34a;
  --blue: #2563eb;
  --yellow: #f59e0b;
  --radius: 14px;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
  --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
  --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

html { scroll-behavior: smooth; }
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; }

.page-header {
  position: sticky; top: 0; z-index: 100;
  background: var(--primary); color: #fff;
  padding: 0 16px; height: 56px;
  display: flex; align-items: center; gap: 12px;
  box-shadow: 0 2px 12px rgba(200,16,46,0.3);
}
.page-header .back-btn {
  background: none; border: none; color: #fff; cursor: pointer;
  display: flex; align-items: center; gap: 6px;
  font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600;
  padding: 8px 4px; transition: opacity var(--transition);
}
.page-header .back-btn:hover { opacity: 0.8; }
.page-header .page-title {
  font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700;
  position: absolute; left: 50%; transform: translateX(-50%);
}

.main { max-width: 700px; margin: 0 auto; padding: 20px 16px 100px; }

/* Profile card */
.profile-card {
  background: var(--surface); border-radius: var(--radius);
  padding: 24px; text-align: center;
  box-shadow: var(--shadow-sm); margin-bottom: 24px;
}
.profile-avatar {
  width: 72px; height: 72px; border-radius: 50%;
  background: var(--primary); color: #fff;
  display: grid; place-items: center; margin: 0 auto 12px;
  font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 700;
}
.profile-name {
  font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700;
  margin-bottom: 4px;
}
.profile-role {
  font-size: 13px; color: var(--text-muted); font-weight: 500;
}

/* Section */
.section-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 12px;
}
.section-title {
  font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700;
  display: flex; align-items: center; gap: 8px;
}
.section-badge {
  background: var(--primary); color: #fff;
  font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 10px;
}
.section-badge.blue { background: var(--blue); }

.history-list { margin-bottom: 28px; }

.history-item {
  background: var(--surface); border-radius: 12px;
  padding: 14px 16px; margin-bottom: 8px;
  box-shadow: var(--shadow-sm);
  display: flex; align-items: center; gap: 12px;
  animation: fadeUp 0.3s ease both;
}
.history-icon {
  width: 40px; height: 40px; border-radius: 10px;
  display: grid; place-items: center; flex-shrink: 0;
}
.history-icon.purchase { background: #fef2f2; color: var(--primary); }
.history-icon.stockin { background: #eff6ff; color: var(--blue); }

.history-icon svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

.history-details { flex: 1; min-width: 0; }
.history-salnum { font-size: 13px; font-weight: 700; }
.history-meta { font-size: 11px; color: var(--text-muted); margin-top: 2px; display: flex; gap: 8px; flex-wrap: wrap; }

.history-right { text-align: right; flex-shrink: 0; }
.history-amount { font-size: 14px; font-weight: 700; }
.history-status {
  font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;
  display: inline-block; margin-top: 3px; text-transform: uppercase;
}
.status-pending { background: #fef3c7; color: #92400e; }
.status-completed, .status-approved { background: #f0fdf4; color: #166534; }
.status-cancelled, .status-rejected { background: #fef2f2; color: #991b1b; }

.empty-state {
  text-align: center; padding: 32px 16px;
  color: var(--text-muted); font-size: 13px;
  background: var(--surface); border-radius: 12px;
  box-shadow: var(--shadow-sm); margin-bottom: 28px;
}

/* Logout button */
.btn-logout {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  width: 100%; padding: 16px;
  background: var(--surface); border: 2px solid #fca5a5;
  border-radius: 12px; color: #dc2626;
  font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 700;
  cursor: pointer; transition: all var(--transition);
  text-decoration: none;
}
.btn-logout:hover { background: #fef2f2; border-color: #dc2626; }
.btn-logout svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
@media (max-width: 480px) {
  .profile-avatar { width: 60px; height: 60px; font-size: 24px; }
  .profile-name { font-size: 18px; }
  .history-item { padding: 12px 14px; }
}
</style>
</head>
<body>

<header class="page-header">
  <button class="back-btn" onclick="history.back()">
    <svg style="width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><polyline points="15 18 9 12 15 6"/></svg>
    Back
  </button>
  <span class="page-title">Account</span>
</header>

<main class="main">
  <!-- Profile Card -->
  <div class="profile-card">
    <div class="profile-avatar"><?php echo strtoupper(substr($userName, 0, 1)); ?></div>
    <div class="profile-name"><?php echo htmlspecialchars($userName); ?></div>
    <div class="profile-role">Staff</div>
  </div>

  <!-- Purchase History -->
  <div class="section-header">
    <div class="section-title">
      Purchase History
      <span class="section-badge"><?php echo count($purchases); ?></span>
    </div>
  </div>
  <div class="history-list">
    <?php if (empty($purchases)): ?>
      <div class="empty-state">No purchase history yet.</div>
    <?php else: ?>
      <?php foreach ($purchases as $i => $p): ?>
        <div class="history-item" style="animation-delay:<?php echo $i * 0.04; ?>s">
          <div class="history-icon purchase">
            <svg><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
          </div>
          <div class="history-details">
            <div class="history-salnum"><?php echo htmlspecialchars($p['SALNUM']); ?></div>
            <div class="history-meta">
              <span><?php echo date('d M Y', strtotime($p['SDATE'])); ?></span>
              <span><?php echo intval($p['item_count']); ?> item<?php echo intval($p['item_count']) !== 1 ? 's' : ''; ?> &middot; Qty <?php echo intval($p['total_qty']); ?></span>
            </div>
          </div>
          <div class="history-right">
            <?php if (floatval($p['total_amount']) > 0): ?>
              <div class="history-amount">RM <?php echo number_format($p['total_amount'], 2); ?></div>
            <?php endif; ?>
            <?php
              $status = strtolower($p['STATUS']);
              $statusClass = 'status-pending';
              if ($status === 'completed' || $status === 'approved') $statusClass = 'status-completed';
              elseif ($status === 'cancelled' || $status === 'rejected') $statusClass = 'status-cancelled';
            ?>
            <span class="history-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($p['STATUS']); ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Stock In History -->
  <div class="section-header">
    <div class="section-title">
      Stock In History
      <span class="section-badge blue"><?php echo count($stockins); ?></span>
    </div>
  </div>
  <div class="history-list">
    <?php if (empty($stockins)): ?>
      <div class="empty-state">No stock in history yet.</div>
    <?php else: ?>
      <?php foreach ($stockins as $i => $s): ?>
        <div class="history-item" style="animation-delay:<?php echo $i * 0.04; ?>s">
          <div class="history-icon stockin">
            <svg><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          </div>
          <div class="history-details">
            <div class="history-salnum"><?php echo htmlspecialchars($s['SALNUM']); ?></div>
            <div class="history-meta">
              <span><?php echo date('d M Y', strtotime($s['SDATE'])); ?></span>
              <span><?php echo intval($s['item_count']); ?> item<?php echo intval($s['item_count']) !== 1 ? 's' : ''; ?> &middot; Qty <?php echo intval($s['total_qty']); ?></span>
            </div>
          </div>
          <div class="history-right">
            <?php
              $status = strtolower($s['STATUS']);
              $statusClass = 'status-pending';
              if ($status === 'completed' || $status === 'approved') $statusClass = 'status-completed';
              elseif ($status === 'cancelled' || $status === 'rejected') $statusClass = 'status-cancelled';
            ?>
            <span class="history-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($s['STATUS']); ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Logout -->
  <a href="logout.php" class="btn-logout">
    <svg><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    Logout
  </a>
</main>

<?php include('mobile-bottombar.php'); ?>

</body>
</html>
