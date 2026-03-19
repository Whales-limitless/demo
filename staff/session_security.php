<?php
/**
 * Secure Session Handler
 *
 * Include this file INSTEAD of calling session_start() directly.
 * Sets a long-lived session cookie (30 days) so users stay logged in
 * on the same device/browser without needing to re-login.
 *
 * The cookie is stored in the device's browser only — it is NOT synced
 * by Chrome/Gmail across devices because it is HttpOnly + SameSite=Strict.
 */

// 30-day session cookie so users stay logged in across browser restarts
$sessionLifetime = 30 * 24 * 60 * 60; // 30 days in seconds

// Extend server-side session garbage collection to match
ini_set('session.gc_maxlifetime', $sessionLifetime);

// Set secure cookie params BEFORE starting the session
session_set_cookie_params([
    'lifetime' => $sessionLifetime, // 30 days — survives browser close
    'path'     => '/',
    'domain'   => '',               // Current domain only
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly'  => true,             // Prevent JavaScript access (not synced by Chrome)
    'samesite' => 'Strict'          // Prevent cross-site cookie sending
]);

session_start();
