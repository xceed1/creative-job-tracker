<?php
// File: traffic/assignment_approve_api.php
// PURPOSE: Approve a SINGLE assignment only
// DOES NOT change job status (by design)

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
ob_start();

require_once "../auth/guard.php";
requireRole(['TRAFFIC']);
require_once "../config/db.php";

require_once "../config/db_context.php";
setDbContext($pdo, 'TRAFFIC');

require_once "../helpers/activity_logger.php";

header('Content-Type: application/json; charset=utf-8');

try {

    $assignmentId = (int)($_POST['assignment_id'] ?? 0);
    $trafficUser  = (int)($_SESSION['user']['id'] ?? 0);

    if ($assignmentId <= 0 || $trafficUser <= 0) {
        throw new RuntimeException('Invalid request');
    }

    $pdo->beginTransaction();

    /* ============================================================
       LOCK ASSIGNMENT + JOB (NO STATUS TRANSITION)
    ============================================================ */
    $stmt = $pdo->prepare("
        SELECT
            ja.id,
            ja.job_id,
            ja.assignment_status,
            jo.is_locked
        FROM job_assignments ja
        JOIN job_orders jo ON jo.id = ja.job_id
        WHERE ja.id = ?
        FOR UPDATE
    ");
    $stmt->execute([$assignmentId]);
    $a = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$a) {
        throw new RuntimeException('Assignment not found');
    }

    if ($a['assignment_status'] !== 'COMPLETED') {
        throw new RuntimeException('Assignment must be COMPLETED to approve');
    }

    $jobId = (int)$a['job_id'];

    /* ============================================================
       APPROVE ASSIGNMENT ONLY
    ============================================================ */
    $stmt = $pdo->prepare("
        UPDATE job_assignments
        SET
            assignment_status = 'APPROVED',
            rejected_note = NULL,
            rejected_at = NULL
        WHERE id = ?
          AND assignment_status = 'COMPLETED'
    ");
    $stmt->execute([$assignmentId]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Approve failed: assignment state changed');
    }

    /* ============================================================
       ACTIVITY LOG (ASSIGNMENT-LEVEL)
    ============================================================ */
    logActivity(
        $pdo,
        $jobId,
        'ASSIGNMENT_APPROVED',
        ['assignment_id' => $assignmentId]
    );

    // 🚫 NO JOB STATUS CHANGE HERE — INTENTIONAL

    $pdo->commit();
    ob_clean();

    echo json_encode([
        'success' => true,
        'job_id'  => $jobId
    ]);
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('[TRAFFIC ASSIGNMENT APPROVE ERROR] ' . $e->getMessage());

    ob_clean();
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
    exit;
}