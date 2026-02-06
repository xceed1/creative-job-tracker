<?php
require_once "../auth/guard.php";
requireRole(['EXECUTIVE']);
require_once "../config/db.php";

header('Content-Type: application/json; charset=utf-8');

$designerId = (int)($_GET['designer_id'] ?? 0);

if ($designerId <= 0) {
    echo json_encode(['success' => false, 'jobs' => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        jo.job_code,
        js.status_name,
        ja.assigned_at,
        jo.updated_at,
        TIMESTAMPDIFF(HOUR, ja.assigned_at, jo.updated_at) AS hours_spent
    FROM job_assignments ja
    JOIN job_orders jo ON ja.job_id = jo.id
    JOIN job_status js ON jo.status_id = js.id
    WHERE ja.designer_id = ?
    ORDER BY ja.assigned_at DESC
");
$stmt->execute([$designerId]);

echo json_encode([
    'success' => true,
    'jobs' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
exit;
