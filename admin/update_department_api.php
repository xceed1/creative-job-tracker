<?php
require_once "../auth/guard.php";
requireRole(['ADMIN']);
require_once "../config/db.php";

$id   = (int)($_POST['dept_id'] ?? 0);
$code = trim($_POST['code'] ?? '');
$name = trim($_POST['name'] ?? '');

if ($id <= 0 || $code === '' || $name === '') {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE departments
    SET dept_code = ?, dept_name = ?
    WHERE id = ?
");
$stmt->execute([$code, $name, $id]);

echo json_encode(['success' => true]);
exit;
