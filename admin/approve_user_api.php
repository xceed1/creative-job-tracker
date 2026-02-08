<?php
require_once "../auth/guard.php";
requireRole(['ADMIN']);
require_once "../config/db.php";
require_once "../helpers/mailer.php";

header('Content-Type: application/json');

$userId = (int)($_POST['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

// Fetch user email
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$email = $stmt->fetchColumn();

if (!$email) {
    echo json_encode(['success' => false]);
    exit;
}

// Generate temporary password
$tempPassword = bin2hex(random_bytes(4));
$hash = password_hash($tempPassword, PASSWORD_DEFAULT);

// Activate user
$pdo->prepare("
    UPDATE users
    SET
        password = ?,
        is_active = 1,
        force_password_change = 1
    WHERE id = ?
")->execute([$hash, $userId]);

// Send email
sendMail(
    $email,
    'Your account access',
    "
    <p>Your account has been approved.</p>
    <p><strong>Temporary password:</strong> {$tempPassword}</p>
    <p>You will be required to change this password immediately after login.</p>
    "
);

echo json_encode(['success' => true]);
exit;
