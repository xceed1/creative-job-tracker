<?php
/**
 * CANONICAL LOGGER
 * ----------------
 * All system activity MUST be logged via logActivity().
 * Legacy loggers are forbidden.
 */


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function logActivity(
    PDO $pdo,
    ?int $jobId,
    string $actionCode,
    string|array|null $context = null
): void {

    if (!isset($_SESSION['user']['id'])) {
        return;
    }

    $userId = (int) $_SESSION['user']['id'];

    // Resolve role_code from DB (source of truth)
    $roleStmt = $pdo->prepare("
        SELECT r.role_code
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $roleStmt->execute([$userId]);
    $roleCode = $roleStmt->fetchColumn();

    if (!$roleCode) {
        return;
    }

    // Prepare meta payload
    $meta = null;
    if (is_array($context)) {
        $meta = json_encode($context, JSON_UNESCAPED_UNICODE);
    } elseif (is_string($context)) {
        $meta = $context;
    }

    $stmt = $pdo->prepare("
        INSERT INTO job_activity_log
        (
            job_id,
            user_id,
            user_role,
            action_code,
            action_label,
            action_meta,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $jobId,
        $userId,
        $roleCode,
        $actionCode,
        $actionCode, // label mirrors code (intentional)
        $meta
    ]);
}
