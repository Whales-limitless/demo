<?php
require_once __DIR__ . '/../staff/session_security.php';

// Only clear admin session variables, preserve staff session
unset(
    $_SESSION['admin_logged_in'],
    $_SESSION['admin_user'],
    $_SESSION['admin_name'],
    $_SESSION['admin_level'],
    $_SESSION['admin_type'],
    $_SESSION['admin_outlet'],
    $_SESSION['admin_permission']
);

// If no other portal is logged in, destroy the entire session
if (empty($_SESSION['user_logged_in'])) {
    session_destroy();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}

header("Location: index.php");
exit;
?>
