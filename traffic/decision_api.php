<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
ob_start();

require_once "../auth/guard.php";
requireRole(['TRAFFIC']);
require_once "../config/db.php";

require_once "../config/db_context.php";
setDbContext($pdo);

require_once "../helpers/job_status_manager.php";
require_once "../helpers/activity_logger.php";

header('Content-Type: application/json; charset=utf-8');

try {

    $jobId  = (int)($_POST['job_id'] ?? 0);
    $action = strtolower((string)($_POST['action'] ?? ''));

    if ($jobId <= 0 || $action !== 'release') {
        throw new RuntimeException('Invalid request');
    }

    $trafficUser = (int)$_SESSION['user']['id'];

    $pdo->beginTransaction();

    /* ============================================================
       LOCK + READ JOB (AUTHORITATIVE)
    ============================================================ */
    $stmt = $pdo->prepare("
        SELECT
            jo.id,
            jo.is_locked,
            js.status_code
        FROM job_orders jo
        JOIN job_status js ON js.id = jo.status_id
        WHERE jo.id = ?
        FOR UPDATE
    ");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        throw new RuntimeException('Job not found');
    }

    if ((int)$job['is_locked'] === 1) {
        throw new RuntimeException('Job already released');
    }

    /* ============================================================
       NORMALIZE STATE (same idea as APPROVE fix)
    ============================================================ */

    // 1) Ensure ALL assignments are approved
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM job_assignments
        WHERE job_id = ?
          AND assignment_status <> 'APPROVED'
    ");
    $stmt->execute([$jobId]);

    if ((int)$stmt->fetchColumn() !== 0) {
        throw new RuntimeException('Cannot release: pending assignments');
    }

    // 2) Ensure job is EXACTLY APPROVED
    if ($job['status_code'] !== 'APPROVED') {
        transitionJobStatus(
            $pdo,
            $jobId,
            'APPROVED',
            'TRAFFIC',
            $trafficUser
        );
    }

    // 🔁 Re-read after normalization
    $stmt = $pdo->prepare("
        SELECT js.status_code
        FROM job_orders jo
        JOIN job_status js ON js.id = jo.status_id
        WHERE jo.id = ?
        FOR UPDATE
    ");
    $stmt->execute([$jobId]);
    $statusNow = (string)$stmt->fetchColumn();

    if ($statusNow !== 'APPROVED') {
        throw new RuntimeException('Job state invalid before release');
    }

    /* ============================================================
       FINAL TRANSITION → RELEASED
    ============================================================ */
    transitionJobStatus(
        $pdo,
        $jobId,
        'RELEASED',
        'TRAFFIC',
        $trafficUser
    );

    logActivity($pdo, $jobId, 'JOB_RELEASED');

    $pdo->commit();

    ob_clean();
    echo json_encode(['success' => true]);
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('[TRAFFIC RELEASE ERROR] ' . $e->getMessage());

    ob_clean();
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
    exit;
}