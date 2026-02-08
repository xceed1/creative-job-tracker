<?php
require_once "../config/db.php";

$token = $_GET['token'] ?? '';
$error = null;

$stmt = $pdo->prepare("
    SELECT user_id
    FROM password_reset_tokens
    WHERE token = ?
      AND used_at IS NULL
      AND expires_at > NOW()
");
$stmt->execute([$token]);
$userId = $stmt->fetchColumn();

if (!$userId) {
    die('Invalid or expired reset link.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($pass === '' || $pass !== $confirm || strlen($pass) < 8) {
        $error = 'Passwords must match and be at least 8 characters.';
    } else {

        $pdo->beginTransaction();

        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $pdo->prepare("
            UPDATE users
            SET
                password = ?,
                force_password_change = 0
            WHERE id = ?
        ")->execute([$hash, $userId]);

        $pdo->prepare("
            UPDATE password_reset_tokens
            SET used_at = NOW()
            WHERE token = ?
        ")->execute([$token]);

        $pdo->commit();

        header("Location: login_form.php");
        exit;
    }
}
?>
<!-- Bootstrap UI here -->
