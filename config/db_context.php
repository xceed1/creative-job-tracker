<?php
// File: config/db_context.php

declare(strict_types=1);

/**
 * Sets MySQL session variables used by triggers.
 * MUST be called AFTER beginTransaction() in any transactional API.
 */
function setDbContext(PDO $pdo, ?string $forceRole = null): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $userId = (int)($_SESSION['user']['id'] ?? 0);
    if ($userId <= 0) {
        throw new RuntimeException('DB context missing user id');
    }

    $role =
        $forceRole
        ?? ($_SESSION['user']['role_code'] ?? null)
        ?? ($_SESSION['user']['role'] ?? null);

    if (!$role) {
        throw new RuntimeException('DB context missing role');
    }

    $role = strtoupper((string)$role);

    // ✅ Force collation on the session var itself
    $stmt = $pdo->prepare("
        SET
          @app_user_id   = ?,
          @app_user_role = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci
    ");
    $stmt->execute([$userId, $role]);
}
