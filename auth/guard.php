<?php

// ✅ ALWAYS start session here
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require specific role(s) to access page
 */
function requireRole(array $roles = []): void
{
    if (!isset($_SESSION['user'])) {
        header("Location: /index.php");
        exit;
    }

    $user = $_SESSION['user'];

    // 🔒 Block inactive users
    if (isset($user['is_active']) && (int)$user['is_active'] !== 1) {
        session_destroy();
        header("Location: /index.php?disabled=1");
        exit;
    }

    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied');
    }
}