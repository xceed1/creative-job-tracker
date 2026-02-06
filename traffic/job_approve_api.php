<?php
http_response_code(410);
echo json_encode([
    'success' => false,
    'error' => 'Deprecated API. Use canonical Traffic APIs.'
]);
exit;

// <?php
throw new RuntimeException('This API is deprecated. Use canonical traffic APIs.');

require_once "../auth/guard.php";
requireRole(['TRAFFIC']);
require_once "../config/db.php";
require_once "../config/db_context.php";
setDbContext($pdo, 'TRAFFIC');

require_once "../helpers/job_status_manager.php";

header('Content-Type: application/json');

$jobId = (int)($_POST['job_id'] ?? 0);

if ($jobId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid job']);
    exit;
}

try {
    $pdo->beginTransaction();

    transitionJobStatus(
        $pdo,
        $jobId,
        'APPROVED',
        'TRAFFIC',
        $_SESSION['user']['id']
    );

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    error_log('[TRAFFIC APPROVE ERROR] ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}