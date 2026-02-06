<?php
declare(strict_types=1);

/* ============================================================
| SESSION
============================================================ */
session_start();

/* ============================================================
| DB GUARD — NEVER ALLOW FATAL ERRORS ON LOGIN
============================================================ */
try {
    require_once "../config/db.php";
} catch (Throwable $e) {

    // Log internally only
    error_log('[LOGIN DB ERROR] ' . $e->getMessage());

    // Redirect silently — do NOT expose system state
    header("Location: login_form.php?error=system");
    exit;
}

/* ============================================================
| METHOD GUARD
============================================================ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login_form.php");
    exit;
}

/* ============================================================
| INPUT VALIDATION
============================================================ */
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header("Location: login_form.php?error=1");
    exit;
}

/* ============================================================
| FETCH USER (SOURCE OF TRUTH)
============================================================ */
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.full_name,
        u.email,
        u.password,
        u.department_id,
        u.is_active,
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

/* ============================================================
| VERIFY PASSWORD
============================================================ */
if (!$user || !password_verify($password, $user['password'])) {
    header("Location: login_form.php?error=1");
    exit;
}

/* ============================================================
| HARD BUSINESS RULE
| USER must belong to a department
============================================================ */
if (
    $user['role_code'] === 'USER' &&
    empty($user['department_id'])
) {
    session_destroy();
    header("Location: login_form.php?error=department_required");
    exit;
}

/* ============================================================
| BUILD SESSION (CANONICAL STRUCTURE)
============================================================ */
$_SESSION['user'] = [
    'id'            => (int)$user['id'],
    'full_name'     => $user['full_name'],
    'email'         => $user['email'],
    'role'          => $user['role_code'],
    'role_id'       => (int)$user['role_id'],
    'department_id' => $user['department_id'] ? (int)$user['department_id'] : null,
    'is_active'     => (int)$user['is_active']
];

/* ============================================================
| REDIRECT BY ROLE
============================================================ */
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