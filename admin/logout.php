<?php
require_once __DIR__ . '/../staff/session_security.php';
require_once __DIR__ . '/../staff/persistent_login.php';

// Revoke persistent token for this device before destroying session
if (!empty($_SESSION['admin_user'])) {
    include_once __DIR__ . '/../staff/dbconnection.php';
    if ($connect) {
        revoke_persistent_token($connect, $_SESSION['admin_user'], 'admin');
    }
}
clear_persistent_cookie();

session_unset();
session_destroy();

// Clear the session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header("Location: index.php");
exit;
?>
