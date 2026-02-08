<?php
require_once "../config/db.php";

$email = strtolower(trim($_POST['email'] ?? ''));

$stmt = $pdo->prepare("
    SELECT id
    FROM users
    WHERE email = ?
      AND is_active = 1
    LIMIT 1
");
$stmt->execute([$email]);
$userId = $stmt->fetchColumn();

if ($userId) {

    $token = bin2hex(random_bytes(32));

    $pdo->prepare("
        INSERT INTO password_reset_tokens
            (user_id, token, expires_at)
        VALUES
            (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
    ")->execute([$userId, $token]);

    require_once "../helpers/mailer.php";

// Send email with reset link
    sendMail(
        $email,
        'Password reset request',
        "
    <p>Click the link below to reset your password:</p>
    <p>
        <a href='https://yourdomain.com/creative-job-tracker/auth/reset_password.php?token={$token}'>
            Reset Password
        </a>
    </p>
    <p>This link expires in 1 hour.</p>
    "
    );

    // /auth/reset_password.php?token=XXXX
}

header("Location: forgot_password.php?sent=1");
exit;
