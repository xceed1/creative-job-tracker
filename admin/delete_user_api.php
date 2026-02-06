<?php
require_once "../auth/guard.php";
requireRole(['ADMIN']);
require_once "../config/db.php";

header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_POST['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$pdo->prepare("
    UPDATE users
    SET is_active = 0
    WHERE id = ?
")->execute([$userId]);

echo json_encode(['success' => true]);
exit;
