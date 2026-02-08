<?php
require_once "../config/db.php";

try {

    $fullName     = trim($_POST['full_name'] ?? '');
    $email        = strtolower(trim($_POST['email'] ?? ''));
    $departmentId = (int)($_POST['department_id'] ?? 0);

    if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $departmentId <= 0) {
        throw new Exception('Invalid input');
    }

    // Email must be unique
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        throw new Exception('An account with this email already exists');
    }

    // USER role
    $roleId = $pdo->query("
        SELECT id FROM roles WHERE role_code = 'USER' LIMIT 1
    ")->fetchColumn();

    if (!$roleId) {
        throw new Exception('System configuration error');
    }

    // Temporary password (never used)
    $passwordHash = password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users
            (full_name, email, password, role_id, department_id, is_active)
        VALUES
            (?, ?, ?, ?, ?, 0)
    ");

    $stmt->execute([
        $fullName,
        $email,
        $passwordHash,
        $roleId,
        $departmentId
    ]);

    header("Location: request_access_form.php?success=1");
    exit;

} catch (Throwable $e) {
    header("Location: request_access_form.php?error=" . urlencode($e->getMessage()));
    exit;
}
