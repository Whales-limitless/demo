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
