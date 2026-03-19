<?php
/**
 * Secure Session Handler
 *
 * Include this file INSTEAD of calling session_start() directly.
 * It hardens session cookies and validates device fingerprints to prevent
 * session hijacking via Chrome cookie sync (same Gmail across PCs).
 */

// Set secure cookie params BEFORE starting the session
session_set_cookie_params([
    'lifetime' => 0,            // Session cookie (expires when browser closes)
    'path'     => '/',
    'domain'   => '',           // Current domain only
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly'  => true,         // Prevent JavaScript access to session cookie
    'samesite' => 'Strict'      // Prevent cross-site cookie sending
]);

session_start();

/**
 * Validate the session fingerprint.
 * If the fingerprint doesn't match (different device using synced cookie),
 * destroy the session and redirect to login.
 *
 * If an existing session has no fingerprint yet (logged in before this update),
 * silently add one so the user is not disrupted.
 */
if (isset($_SESSION['_fingerprint'])) {
    $current_fingerprint = _generate_fingerprint();
    if (!hash_equals($_SESSION['_fingerprint'], $current_fingerprint)) {
        // Fingerprint mismatch — likely a different device with a synced cookie
        session_unset();
        session_destroy();

        // Clear the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Restart a clean session for the login page
        session_start();

        // Determine redirect based on current path
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if (strpos($script, '/admin/') !== false) {
            header("Location: index.php");
        } else {
            header("Location: login.php");
        }
        exit;
    }
} elseif (!empty($_SESSION['user_logged_in']) || !empty($_SESSION['admin_logged_in'])) {
    // Existing session from before the update — backfill fingerprint so they
    // stay logged in without interruption.
    $_SESSION['_fingerprint'] = _generate_fingerprint();
}

/**
 * Auto-restore from persistent cookie when the session is empty (e.g. browser
 * was closed and reopened). This runs on EVERY page that includes this file,
 * so users stay logged in seamlessly as long as the pw_remember cookie is valid.
 */
if (empty($_SESSION['user_logged_in']) && empty($_SESSION['admin_logged_in'])) {
    // Determine portal from current path
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $_auto_portal = (strpos($script, '/admin/') !== false) ? 'admin' : 'staff';
    if (try_persistent_restore($_auto_portal)) {
        // Session restored — no redirect needed, page continues normally
    }
    unset($_auto_portal);
}

/**
 * Generate a fingerprint combining User-Agent and client IP.
 * Different PCs will have different IPs, so even if Chrome syncs the cookie,
 * the session won't validate on another machine.
 */
function _generate_fingerprint(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash('sha256', $ua . '|' . $ip);
}

/**
 * Call this AFTER a successful login to bind the session to this device.
 * Also regenerates the session ID to prevent session fixation.
 */
function set_session_fingerprint(): void {
    session_regenerate_id(true);  // Destroy old session, create new ID
    $_SESSION['_fingerprint'] = _generate_fingerprint();
}

/**
 * Attempt to restore a session from a persistent login cookie.
 * Call this on pages that require authentication, AFTER checking that
 * the normal session is not active.
 *
 * @param string $portal 'staff' or 'admin'
 * @return bool True if session was restored
 */
function try_persistent_restore(string $portal = 'staff'): bool {
    try {
        require_once __DIR__ . '/persistent_login.php';

        if (empty($_COOKIE[PERSISTENT_COOKIE_NAME])) {
            return false;
        }

        include_once __DIR__ . '/dbconnection.php';
        global $connect;

        if (!$connect || $connect->connect_errno) {
            return false;
        }

        $user = restore_persistent_session($connect, $portal);
        if (!$user) {
            return false;
        }

        // Bind session to this device
        set_session_fingerprint();

        if ($portal === 'admin') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $user['USER1'] ?? '';
            $_SESSION['admin_name'] = $user['USER_NAME'] ?? $user['USERNAME'] ?? $user['USER1'] ?? '';
            $_SESSION['admin_level'] = $user['LEVEL'] ?? 0;
            $_SESSION['admin_type'] = $user['TYPE'] ?? '';
            $_SESSION['admin_outlet'] = $user['OUTLET'] ?? '';
            $_SESSION['admin_permission'] = $user['PERMISSION'] ?? 'FULL';
        } else {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user['ID'] ?? '';
            $_SESSION['user_username'] = $user['USER1'] ?? '';
            $_SESSION['user_name'] = $user['USER_NAME'] ?? $user['USERNAME'] ?? $user['USER1'] ?? '';
            $_SESSION['user_code'] = $user['USERNAME'] ?? '';
            $_SESSION['user_level'] = $user['LEVEL'] ?? 0;
            $_SESSION['user_outlet'] = $user['OUTLET'] ?? '';
            $_SESSION['user_dept'] = $user['DEPT'] ?? '';
            $_SESSION['user_type'] = $user['TYPE'] ?? 'S';
            $_SESSION['user_permission'] = $user['PERMISSION'] ?? 'FULL';

            // Load branch name
            $_SESSION['user_branch_code'] = $user['OUTLET'] ?? '';
            $_SESSION['user_branch_name'] = '';
            if (!empty($user['OUTLET'])) {
                $brStmt = $connect->prepare("SELECT `name` FROM `branch` WHERE `code` = ? LIMIT 1");
                if ($brStmt) {
                    $brStmt->bind_param("s", $user['OUTLET']);
                    $brStmt->execute();
                    $brResult = $brStmt->get_result();
                    if ($brResult && $brRow = $brResult->fetch_assoc()) {
                        $_SESSION['user_branch_name'] = $brRow['name'];
                    }
                    $brStmt->close();
                }
            }
        }

        return true;
    } catch (\Throwable $e) {
        // Prevent 500 errors — if persistent restore fails, just fall through to login
        return false;
    }
}
