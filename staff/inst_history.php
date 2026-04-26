<?php
require_once __DIR__ . '/session_security.php';
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$userType = $_SESSION['user_type'] ?? 'S';
if ($userType !== 'D' && $userType !== 'A') {
    header("Location: index.php");
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

// Auto-create table if not exists
$connect->query("CREATE TABLE IF NOT EXISTS `inst_job` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `USERCODE` varchar(50) NOT NULL,
  `USERNAME` varchar(80) NOT NULL,
  `IMAGE` varchar(200) NOT NULL DEFAULT '',
  `REMARK` text NOT NULL,
  `STATUS` varchar(1) NOT NULL DEFAULT 'P',
  `REJECT_REASON` text NOT NULL,
  `APPROVE_REASON` text NOT NULL,
  `COMMISSION` double(10,2) NOT NULL DEFAULT 0.00,
  `SUBMIT_DATETIME` datetime NOT NULL,
  `REVIEWED_BY` varchar(50) NOT NULL DEFAULT '',
  `REVIEWED_DATETIME` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_user` (`USERCODE`),
  KEY `idx_status` (`STATUS`),
  KEY `idx_submit` (`SUBMIT_DATETIME`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$userCode = $_SESSION['user_code'] ?? '';
$jobs = [];
if ($userCode !== '') {
    $stmt = $connect->prepare("SELECT * FROM `inst_job` WHERE USERCODE = ? ORDER BY SUBMIT_DATETIME DESC, ID DESC");
    $stmt->bind_param("s", $userCode);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($r = $result->fetch_assoc()) { $jobs[] = $r; }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation History</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="components.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6; --text: #1a1a1a; --text-muted: #6b7280; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding-bottom: 80px; min-height: 100vh; }
        h1, h2, h3 { font-family: 'Outfit', sans-serif; }
        .page-header { position: sticky; top: 0; z-index: 100; background: var(--primary); color: #fff; padding: 0 16px; height: 56px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 12px rgba(200,16,46,0.3); }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; flex: 1; }
        .header-action { background: rgba(255,255,255,0.18); color: #fff; border: none; padding: 8px 14px; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .header-action:hover { background: rgba(255,255,255,0.28); }
        .header-action svg { width: 16px; height: 16px; }
        .main-content { max-width: 700px; margin: 0 auto; padding: 16px; }

        .job-count { font-size: 13px; color: var(--text-muted); margin-bottom: 12px; }
        .job-count strong { color: var(--text); }

        .job-card { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04); padding: 16px; margin-bottom: 12px; }
        .job-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
        .job-meta { font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .job-by { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 600; }

        .job-badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .job-badge.pending { background: #fef3c7; color: #92400e; }
        .job-badge.approved { background: #dcfce7; color: #16a34a; }
        .job-badge.rejected { background: #fee2e2; color: #dc2626; }

        .job-image { width: 100%; max-height: 280px; object-fit: cover; border-radius: 10px; margin-bottom: 10px; cursor: pointer; }
        .job-remark { font-size: 13px; color: var(--text); margin-bottom: 8px; line-height: 1.5; }
        .job-remark strong { color: var(--text-muted); font-weight: 600; }
        .job-extra { font-size: 12px; color: var(--text-muted); padding-top: 8px; border-top: 1px solid #f3f4f6; margin-top: 8px; line-height: 1.6; }
        .job-extra strong { color: var(--text); }
        .job-commission { color: #16a34a; font-weight: 700; }

        .empty-state { text-align: center; padding: 48px 16px; color: var(--text-muted); }
        .empty-state svg { width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5; }
        .empty-state p { font-size: 14px; }

        /* Image modal */
        .img-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 200; align-items: center; justify-content: center; padding: 16px; }
        .img-overlay.active { display: flex; }
        .img-overlay img { max-width: 100%; max-height: 90vh; border-radius: 8px; }
        .img-close { position: absolute; top: 16px; right: 16px; background: rgba(255,255,255,0.2); border: none; color: #fff; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .img-close svg { width: 20px; height: 20px; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <span class="page-title">Installation History</span>
        <a href="inst_job.php" class="header-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New
        </a>
    </header>

    <div class="main-content">
        <?php if ($userCode === ''): ?>
            <div class="empty-state">
                <p>Your account has no user code.</p>
            </div>
        <?php else: ?>
            <div class="job-count">Showing <strong><?php echo count($jobs); ?></strong> installation job(s)</div>

            <?php if (count($jobs) === 0): ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>
                </svg>
                <p>No installation jobs submitted yet.</p>
            </div>
            <?php else: ?>
                <?php foreach ($jobs as $j):
                    $status = $j['STATUS'];
                    $statusClass = $status === 'A' ? 'approved' : ($status === 'R' ? 'rejected' : 'pending');
                    $statusLabel = $status === 'A' ? 'Approved' : ($status === 'R' ? 'Rejected' : 'Pending');
                ?>
                <div class="job-card">
                    <div class="job-card-top">
                        <div>
                            <div class="job-by"><?php echo htmlspecialchars($j['USERNAME'] ?: $j['USERCODE']); ?></div>
                            <div class="job-meta">Submitted: <?php echo htmlspecialchars($j['SUBMIT_DATETIME']); ?></div>
                        </div>
                        <span class="job-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                    </div>
                    <?php if (!empty($j['IMAGE'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($j['IMAGE']); ?>" alt="Installation photo" class="job-image" onclick="openImg(this.src)">
                    <?php endif; ?>
                    <?php if (!empty($j['REMARK'])): ?>
                    <div class="job-remark"><strong>Remark:</strong> <?php echo nl2br(htmlspecialchars($j['REMARK'])); ?></div>
                    <?php endif; ?>
                    <?php if ($status === 'A' || $status === 'R'): ?>
                    <div class="job-extra">
                        <?php if ($status === 'A' && (float)$j['COMMISSION'] > 0): ?>
                        <div><strong>Commission:</strong> <span class="job-commission">RM <?php echo number_format((float)$j['COMMISSION'], 2); ?></span></div>
                        <?php endif; ?>
                        <?php if ($status === 'A' && !empty($j['APPROVE_REASON'])): ?>
                        <div><strong>Approve note:</strong> <?php echo nl2br(htmlspecialchars($j['APPROVE_REASON'])); ?></div>
                        <?php endif; ?>
                        <?php if ($status === 'R' && !empty($j['REJECT_REASON'])): ?>
                        <div><strong>Reject reason:</strong> <?php echo nl2br(htmlspecialchars($j['REJECT_REASON'])); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($j['REVIEWED_DATETIME'])): ?>
                        <div><strong>Reviewed:</strong> <?php echo htmlspecialchars($j['REVIEWED_DATETIME']); ?> <?php if (!empty($j['REVIEWED_BY'])): ?>by <?php echo htmlspecialchars($j['REVIEWED_BY']); ?><?php endif; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="img-overlay" id="imgOverlay" onclick="closeImg()">
        <button class="img-close" onclick="closeImg()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <img id="imgFull" alt="Full size">
    </div>

    <?php include 'mobile-bottombar.php'; ?>

    <script>
    function openImg(src) {
        document.getElementById('imgFull').src = src;
        document.getElementById('imgOverlay').classList.add('active');
    }
    function closeImg() {
        document.getElementById('imgOverlay').classList.remove('active');
    }
    </script>
</body>
</html>
