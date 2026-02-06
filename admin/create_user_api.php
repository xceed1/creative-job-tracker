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

    /* -------- INPUT -------- */
    $fullName     = trim($_POST['full_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $roleId       = (int)($_POST['role_id'] ?? 0);
    $departmentId = $_POST['department_id'] !== '' ? (int)$_POST['department_id'] : null;

    if ($fullName === '' || $email === '' || $password === '' || $roleId <= 0) {
        throw new Exception('Missing required fields');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email');
    }

    /* -------- CHECK EMAIL UNIQUE -------- */
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetch()) {
        throw new Exception('Email already exists');
    }

    /* -------- HASH PASSWORD -------- */
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    /* -------- INSERT USER -------- */
    $stmt = $pdo->prepare("
        INSERT INTO users
        (
            full_name,
            email,
            password,
            role_id,
            department_id,
            is_active,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, 1, NOW())
    ");

    $stmt->execute([
        $fullName,
        $email,
        $passwordHash,
        $roleId,
        $departmentId
    ]);

    ob_clean();
    echo json_encode(['success' => true]);
    exit;

} catch (Throwable $e) {

    error_log('[CREATE USER ERROR] ' . $e->getMessage());

    ob_clean();
    echo json_encode([
        'success' => false,
        'error'   => 'Server error'
    ]);
    exit;
}
