<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once "../auth/guard.php";
requireRole(['USER']);
require_once "../config/db.php";
require_once "../config/db_context.php";
setDbContext($pdo);
require_once "../helpers/activity_logger.php";
require_once "../config/paths.php";


header('Content-Type: application/json; charset=utf-8');

try {

    $userId = (int)($_SESSION['user']['id'] ?? 0);
    $jobId  = (int)($_POST['job_id'] ?? 0);

    if ($userId <= 0 || $jobId <= 0) {
        throw new Exception('Invalid request');
    }

    /* ---------------------------------
    | FETCH JOB (OWNERSHIP + LOCK)
    ----------------------------------*/
    $stmt = $pdo->prepare("
        SELECT
            job_code,
            project_name,
            job_subject,
            received_format_file
        FROM job_orders
        WHERE id = ?
          AND created_by = ?
          AND is_locked = 0
    ");
    $stmt->execute([$jobId, $userId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        throw new Exception('Job not found or locked');
    }

/* ---------------------------------
| DELETE ATTACHMENT FILE (IF ANY)
----------------------------------*/
if (!empty($job['received_format_file'])) {

    // DB stores: uploads/received_formats/uuid.ext
    $absolutePath = dirname(__DIR__) . '/' . $job['received_format_file'];

    if (is_file($absolutePath)) {
        unlink($absolutePath);
    } else {
        error_log('[DELETE JOB] File not found: ' . $absolutePath);
    }
}

    /* ---------------------------------
    | LOG ACTIVITY (BEFORE DELETE)
    ----------------------------------*/
    logActivity(
        $pdo,
        $jobId,
        'USER_DELETE_JOB',
        [
            'job_code'    => $job['job_code'],
            'project'     => $job['project_name'],
            'job_subject' => $job['job_subject']
        ]
    );

    /* ---------------------------------
    | DELETE JOB
    ----------------------------------*/
    $pdo->prepare("
        DELETE FROM job_orders
        WHERE id = ?
          AND created_by = ?
          AND is_locked = 0
    ")->execute([$jobId, $userId]);

    echo json_encode(['success' => true]);
    exit;
} catch (Throwable $e) {

    error_log('[DELETE JOB ERROR] ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error'   => 'Delete failed'
    ]);
    exit;
}