<?php
require_once __DIR__ . '/session_security.php';

// Only clear staff session variables, preserve admin session
unset(
    $_SESSION['user_logged_in'],
    $_SESSION['user_id'],
    $_SESSION['user_username'],
    $_SESSION['user_name'],
    $_SESSION['user_code'],
    $_SESSION['user_level'],
    $_SESSION['user_outlet'],
    $_SESSION['user_dept'],
    $_SESSION['user_type'],
    $_SESSION['user_permission'],
    $_SESSION['user_branch_code'],
    $_SESSION['user_branch_name']
);

// If no other portal is logged in, destroy the entire session
if (empty($_SESSION['admin_logged_in'])) {
    session_destroy();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}

header("Location: login.php");
exit;
?>
