<?php
require_once "../auth/guard.php";
requireRole(['ADMIN']);
require_once "../config/db.php";

$id = (int)($_POST['dept_id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$pdo->prepare("
    UPDATE departments
    SET is_active = NOT is_active
    WHERE id = ?
")->execute([$id]);

echo json_encode(['success' => true]);
exit;
