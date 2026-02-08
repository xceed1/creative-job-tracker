<?php
declare(strict_types=1);

/* ---------- SILENT MODE ---------- */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

ob_start();

require_once "../auth/guard.php";
requireRole(['ADMIN']);
require_once "../config/db.php";

header('Content-Type: application/json; charset=utf-8');

try {

    $userId       = (int)($_POST['user_id'] ?? 0);
    $fullName     = trim($_POST['full_name'] ?? '');
    $roleId       = (int)($_POST['role_id'] ?? 0);
    $departmentId = $_POST['department_id'] !== '' ? (int)$_POST['department_id'] : null;
    $isActive     = (int)($_POST['is_active'] ?? 1);

    if ($userId <= 0 || $fullName === '' || $roleId <= 0) {
        throw new Exception('Invalid input');
    }

    /* -------- FETCH CURRENT USER STATE -------- */
    $stmt = $pdo->prepare("
        SELECT is_active
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current) {
        throw new Exception('User not found');
    }

    $tempPassword = null;

    /* -------- ACTIVATION LOGIC -------- */
    if ((int)$current['is_active'] === 0 && $isActive === 1) {

        // Generate temp password
        $tempPassword = bin2hex(random_bytes(4));
        $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);

        $pdo->prepare("
            UPDATE users
            SET
                password = ?,
                force_password_change = 1
            WHERE id = ?
        ")->execute([$passwordHash, $userId]);
    }

    /* -------- UPDATE USER -------- */
    $stmt = $pdo->prepare("
        UPDATE users
        SET
            full_name     = ?,
            role_id       = ?,
            department_id = ?,
            is_active     = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $fullName,
        $roleId,
        $departmentId,
        $isActive,
        $userId
    ]);

    ob_clean();
    echo json_encode([
        'success' => true,
        'temp_password' => $tempPassword
    ]);
    exit;

} catch (Throwable $e) {

    error_log('[UPDATE USER ERROR] ' . $e->getMessage());

    ob_clean();
    echo json_encode([
        'success' => false,
        'error'   => 'Server error'
    ]);
    exit;
}
