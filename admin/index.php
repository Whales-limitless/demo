<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include('../dbconnection.php');

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $connect->prepare("SELECT * FROM `sysfile` WHERE `USER1` = ? AND `USER2` = ? LIMIT 1");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $username;
            $_SESSION['admin_name'] = $user['USER_NAME'] ?? $user['USERNAME'] ?? $username;
            $_SESSION['admin_level'] = $user['LEVEL'] ?? 0;
            $_SESSION['admin_outlet'] = $user['OUTLET'] ?? '';
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
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
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
    --shadow-lg: 0 8px 30px rgba(0,0,0,0.12);
    --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    -webkit-font-smoothing: antialiased;
}

.login-container {
    width: 100%;
    max-width: 420px;
    padding: 16px;
}

.login-card {
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    padding: 40px 32px;
    animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}

.login-header {
    text-align: center;
    margin-bottom: 32px;
}

.login-header .logo {
    width: 64px;
    height: 64px;
    background: var(--primary);
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

.login-header .logo svg {
    width: 32px;
    height: 32px;
    fill: none;
    stroke: #fff;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.login-header h1 {
    font-family: 'Outfit', sans-serif;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 4px;
}

.login-header p {
    color: var(--text-muted);
    font-size: 14px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--text);
}

.form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    color: var(--text);
    outline: none;
    transition: border-color var(--transition), box-shadow var(--transition);
}

.form-group input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(200, 16, 46, 0.1);
}

.error-msg {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-login {
    width: 100%;
    padding: 13px;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition), transform var(--transition);
}

.btn-login:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

.btn-login:active {
    transform: translateY(0);
}

@media (max-width: 480px) {
    .login-card { padding: 32px 24px; }
}
</style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="logo">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
            </div>
            <h1>Admin Panel</h1>
            <p>Sign in to continue</p>
        </div>

        <?php if ($error): ?>
        <div class="error-msg">
            <svg style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn-login">Sign In</button>
        </form>
    </div>
</div>

</body>
</html>
