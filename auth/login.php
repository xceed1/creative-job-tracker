<?php
declare(strict_types=1);

session_start();

try {
    require_once "../config/db.php";
} catch (Throwable $e) {
    error_log('[LOGIN DB ERROR] ' . $e->getMessage());
    header("Location: login_form.php?error=system");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login_form.php");
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header("Location: login_form.php?error=1");
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.full_name,
        u.email,
        u.password,
        u.department_id,
        u.is_active,
        u.force_password_change,
        r.id AS role_id,
        r.role_code
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.email = ?
      AND u.is_active = 1
    LIMIT 1
");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    header("Location: login_form.php?error=1");
    exit;
}

if (
    $user['role_code'] === 'USER' &&
    empty($user['department_id'])
) {
    session_destroy();
    header("Location: login_form.php?error=department_required");
    exit;
}

$_SESSION['user'] = [
    'id'                    => (int)$user['id'],
    'full_name'             => $user['full_name'],
    'email'                 => $user['email'],
    'role'                  => $user['role_code'],
    'role_id'               => (int)$user['role_id'],
    'department_id'         => $user['department_id'] ? (int)$user['department_id'] : null,
    'is_active'             => (int)$user['is_active'],
    'force_password_change' => (int)$user['force_password_change']
];

if ((int)$user['force_password_change'] === 1) {
    header("Location: change_password.php");
    exit;
}

switch ($user['role_code']) {
    case 'ADMIN':
        header("Location: ../admin/dashboard.php");
        break;
    case 'TRAFFIC':
        header("Location: ../traffic/dashboard.php");
        break;
    case 'DESIGNER':
        header("Location: ../designer/dashboard.php");
        break;
    case 'EXECUTIVE':
        header("Location: ../admin/performance_dashboard.php");
        break;
    case 'USER':
    default:
        header("Location: ../user/dashboard.php");
        break;
}

exit;
