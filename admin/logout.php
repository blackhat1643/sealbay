<?php
/**
 * Admin sign-out (POST only, CSRF-protected).
 */
require __DIR__ . '/includes/admin.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && csrf_valid()) {
    $_SESSION = [];
    session_regenerate_id(true);
}
redirect(admin_url('login.php'));
