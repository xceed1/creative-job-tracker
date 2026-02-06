<?php
require_once "../auth/guard.php";
requireRole(['ADMIN']);
require_once "../config/db.php";

$code = trim($_POST['code'] ?? '');
$name = trim($_POST['name'] ?? '');

if ($code === '' || $name === '') {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO departments (dept_code, dept_name, is_active)
    VALUES (?, ?, 1)
");
$stmt->execute([$code, $name]);

echo json_encode(['success' => true]);
exit;
