<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Staff');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Home</title>
<!-- PWA Meta Tags (must be in <head> for install prompt) -->
<link rel="manifest" href="/staff/manifest.json">
<meta name="theme-color" content="#C8102E">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="PWSTAFF">
<link rel="apple-touch-icon" href="/staff/icons/icon-152.png">
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
  --radius: 14px;
  --shadow-lg: 0 8px 30px rgba(0,0,0,0.12);
  --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
}

.main { max-width: 960px; margin: 0 auto; padding: 24px 16px 90px; }

.welcome-card {
  background: var(--surface);
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
  padding: 40px 32px;
  text-align: center;
  animation: fadeUp 0.4s ease both;
}

.welcome-card h1 {
  font-family: 'Outfit', sans-serif;
  font-size: 28px;
  font-weight: 700;
  margin-bottom: 8px;
}

.welcome-card .user-name {
  color: var(--primary);
}

.welcome-card p {
  color: var(--text-muted);
  font-size: 15px;
}

@keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 480px) {
  .welcome-card { padding: 32px 20px; }
  .welcome-card h1 { font-size: 22px; }
}
</style>
</head>
<body>

<?php include('navbar.php'); ?>

<main class="main">
  <div class="welcome-card">
    <h1>Welcome, <span class="user-name"><?php echo $userName; ?></span></h1>
    <?php
    $indexType = $_SESSION['user_type'] ?? 'S';
    $portalLabel = 'staff';
    if ($indexType === 'A') $portalLabel = 'admin';
    elseif ($indexType === 'D') $portalLabel = 'delivery';
    ?>
    <p>You are logged in as <?php echo $portalLabel; ?>.</p>
  </div>
</main>

<?php include('mobile-bottombar.php'); ?>

</body>
</html>
