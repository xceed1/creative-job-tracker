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
    echo json_encode(['success' => true]);
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
