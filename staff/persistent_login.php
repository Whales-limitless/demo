<?php
/**
 * Persistent Login (Remember Me) - Device-Specific Tokens
 *
 * Each token is bound to a device fingerprint (User-Agent + IP).
 * Logging in on PC-A with company Gmail will NOT affect PC-B sessions.
 *
 * Table required (auto-created if missing):
 *   persistent_tokens(id, username, token_hash, device_fingerprint, portal, created_at, expires_at)
 */

define('PERSISTENT_COOKIE_NAME', 'pw_remember');
define('PERSISTENT_COOKIE_DAYS', 30);

/**
 * Ensure the persistent_tokens table exists.
 */
function _ensure_persistent_table(mysqli $db): void {
    $db->query("CREATE TABLE IF NOT EXISTS `persistent_tokens` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) NOT NULL,
        `token_hash` VARCHAR(64) NOT NULL,
        `device_fingerprint` VARCHAR(64) NOT NULL,
        `portal` ENUM('staff','admin') NOT NULL DEFAULT 'staff',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `expires_at` DATETIME NOT NULL,
        INDEX idx_token (`token_hash`),
        INDEX idx_user_device (`username`, `device_fingerprint`, `portal`),
        INDEX idx_expires (`expires_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Generate a device fingerprint for persistent tokens (same logic as session_security).
 */
function _persistent_fingerprint(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash('sha256', $ua . '|' . $ip);
}

/**
 * Issue a persistent login token after successful authentication.
 * Replaces any existing token for same user+device+portal combo.
 *
 * @param mysqli $db       Database connection
 * @param string $username The username (USER1)
 * @param string $portal   'staff' or 'admin'
 */
function issue_persistent_token(mysqli $db, string $username, string $portal = 'staff'): void {
    _ensure_persistent_table($db);

    $fingerprint = _persistent_fingerprint();
    $token = bin2hex(random_bytes(32)); // 64-char random token
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + (PERSISTENT_COOKIE_DAYS * 86400));

    // Remove old tokens for this user+device+portal (one token per device per portal)
    $del = $db->prepare("DELETE FROM `persistent_tokens` WHERE `username` = ? AND `device_fingerprint` = ? AND `portal` = ?");
    $del->bind_param("sss", $username, $fingerprint, $portal);
    $del->execute();
    $del->close();

    // Insert new token
    $ins = $db->prepare("INSERT INTO `persistent_tokens` (`username`, `token_hash`, `device_fingerprint`, `portal`, `expires_at`) VALUES (?, ?, ?, ?, ?)");
    $ins->bind_param("sssss", $username, $tokenHash, $fingerprint, $portal, $expiresAt);
    $ins->execute();
    $ins->close();

    // Set cookie: value = "username|token" (token is unhashed, we verify by hashing it)
    $cookieValue = $username . '|' . $token . '|' . $portal;
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie(PERSISTENT_COOKIE_NAME, $cookieValue, [
        'expires'  => time() + (PERSISTENT_COOKIE_DAYS * 86400),
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly'  => true,
        'samesite' => 'Strict'
    ]);
}

/**
 * Attempt to restore a session from the persistent cookie.
 * Returns the user row from sysfile if valid, or null.
 *
 * @param mysqli $db     Database connection
 * @param string $portal 'staff' or 'admin'
 * @return array|null    User row or null
 */
function restore_persistent_session(mysqli $db, string $portal = 'staff'): ?array {
    if (empty($_COOKIE[PERSISTENT_COOKIE_NAME])) {
        return null;
    }

    $parts = explode('|', $_COOKIE[PERSISTENT_COOKIE_NAME], 3);
    if (count($parts) !== 3) {
        clear_persistent_cookie();
        return null;
    }

    [$username, $token, $cookiePortal] = $parts;

    // Only restore for the matching portal
    if ($cookiePortal !== $portal) {
        return null;
    }

    _ensure_persistent_table($db);

    $tokenHash = hash('sha256', $token);
    $fingerprint = _persistent_fingerprint();

    // Find valid token matching username + token hash + device fingerprint + portal
    $stmt = $db->prepare(
        "SELECT * FROM `persistent_tokens`
         WHERE `username` = ? AND `token_hash` = ? AND `device_fingerprint` = ? AND `portal` = ? AND `expires_at` > NOW()
         LIMIT 1"
    );
    $stmt->bind_param("ssss", $username, $tokenHash, $fingerprint, $portal);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        clear_persistent_cookie();
        return null;
    }
    $stmt->close();

    // Token is valid — look up the user from sysfile
    if ($portal === 'admin') {
        $userStmt = $db->prepare("SELECT * FROM `sysfile` WHERE `USER1` = ? AND `TYPE` = 'A' LIMIT 1");
    } else {
        $userStmt = $db->prepare("SELECT * FROM `sysfile` WHERE `USER1` = ? AND `TYPE` IN ('A','S','D') LIMIT 1");
    }
    $userStmt->bind_param("s", $username);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows === 0) {
        $userStmt->close();
        // User no longer exists or type changed — clean up
        revoke_persistent_token($db, $username, $portal);
        clear_persistent_cookie();
        return null;
    }

    $user = $userResult->fetch_assoc();
    $userStmt->close();

    // Rotate the token (issue a fresh one for security)
    issue_persistent_token($db, $username, $portal);

    return $user;
}

/**
 * Revoke persistent tokens for a user on the current device.
 */
function revoke_persistent_token(mysqli $db, string $username, string $portal = 'staff'): void {
    _ensure_persistent_table($db);
    $fingerprint = _persistent_fingerprint();
    $stmt = $db->prepare("DELETE FROM `persistent_tokens` WHERE `username` = ? AND `device_fingerprint` = ? AND `portal` = ?");
    $stmt->bind_param("sss", $username, $fingerprint, $portal);
    $stmt->execute();
    $stmt->close();
}

/**
 * Clear the persistent login cookie from the browser.
 */
function clear_persistent_cookie(): void {
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie(PERSISTENT_COOKIE_NAME, '', [
        'expires'  => time() - 86400,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly'  => true,
        'samesite' => 'Strict'
    ]);
}

/**
 * Clean up expired tokens (call periodically).
 */
function cleanup_expired_tokens(mysqli $db): void {
    _ensure_persistent_table($db);
    $db->query("DELETE FROM `persistent_tokens` WHERE `expires_at` < NOW()");
}
