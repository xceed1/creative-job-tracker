<?php

/**
 * CANONICAL API — TRAFFIC DISPATCH (MULTI-DESIGNER READY)
 * ------------------------------------------------------
 * - Allows dispatching SAME job to MULTIPLE designers
 * - One row per assignment
 * - No overwriting
 * - No workflow logic changes
 */

declare(strict_types=1);

/* ---------- OUTPUT SAFETY ---------- */
ini_set('display_errors', '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);
ob_start();

require_once "../auth/guard.php";
requireRole(['TRAFFIC']);
require_once "../config/db.php";
require_once "../config/db_context.php";

require_once "../helpers/activity_logger.php";
require_once "../helpers/job_status_manager.php";

header('Content-Type: application/json; charset=utf-8');

try {

    /* ============================================================
       BEGIN TRANSACTION (CRITICAL FIX)
    ============================================================ */
    $pdo->beginTransaction();

    // 🔒 DB context MUST be set inside transaction
    setDbContext($pdo, 'TRAFFIC');

    /* ---------- INPUT ---------- */
    $jobId        = (int)($_POST['job_id'] ?? 0);
    $designerId   = (int)($_POST['designer_id'] ?? 0);
    $hours        = (float)($_POST['estimated_hours'] ?? 0);
    $trafficNote  = trim($_POST['traffic_note'] ?? '');
    $trafficUser  = (int)($_SESSION['user']['id'] ?? 0);

    if ($jobId <= 0 || $designerId <= 0 || $hours <= 0) {
        throw new Exception('Invalid input');
    }

    /* ---------- VERIFY JOB ---------- */
    $jobStmt = $pdo->prepare("
        SELECT id, status_id
        FROM job_orders
        WHERE id = ?
          AND is_locked = 0
        FOR UPDATE
    ");
    $jobStmt->execute([$jobId]);
    $job = $jobStmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        throw new Exception('Job not found or locked');
    }

    /* ---------- PREVENT DUPLICATE ACTIVE ASSIGNMENT ---------- */
    $dupCheck = $pdo->prepare("
        SELECT id
        FROM job_assignments
        WHERE job_id = ?
          AND designer_id = ?
          AND assignment_status IN ('ASSIGNED', 'IN_PROGRESS')
        LIMIT 1
    ");
    $dupCheck->execute([$jobId, $designerId]);

    if ($dupCheck->fetch()) {
        throw new Exception('Designer already assigned to this job');
    }

    /* ---------- CREATE ASSIGNMENT ---------- */
    $assignStmt = $pdo->prepare("
        INSERT INTO job_assignments (
            job_id,
            designer_id,
            assigned_by,
            assignment_status,
            estimated_hours,
            traffic_note,
            assigned_at,
            assigned_due_at
        ) VALUES (
            ?, ?, ?, 'ASSIGNED', ?, ?, NOW(),
            DATE_ADD(NOW(), INTERVAL ? HOUR)
        )
    ");

    if ($trafficUser <= 0) {
        throw new Exception('Traffic user not authenticated');
    }

    $assignStmt->execute([
        $jobId,
        $designerId,
        $trafficUser,
        $hours,
        $trafficNote,
        $hours
    ]);

    $assignmentId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM job_assignments
    WHERE job_id = ?
");
    $stmt->execute([$jobId]);

    if ((int)$stmt->fetchColumn() === 0) {
        throw new RuntimeException('Cannot dispatch without assignments');
    }

    /* ---------- MOVE JOB → DISPATCHED (CANONICAL) ---------- */
    transitionJobStatus(
        $pdo,
        $jobId,
        'DISPATCHED',
        'TRAFFIC',
        $trafficUser
    );

    /* ---------- ACTIVITY LOG ---------- */
    logActivity(
        $pdo,
        $jobId,
        'TRAFFIC_DISPATCH_ASSIGNMENT',
        [
            'assignment_id'   => $assignmentId,
            'designer_id'     => $designerId,
            'estimated_hours' => $hours
        ]
    );

    /* ============================================================
       COMMIT (ALL OR NOTHING)
    ============================================================ */
    $pdo->commit();

    ob_clean();
    echo json_encode([
        'success'        => true,
        'assignment_id' => $assignmentId
    ]);
    exit;
} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('[DISPATCH API ERROR] ' . $e->getMessage());
    throw $e; // TEMPORARY — LET IT CRASH


    ob_clean();
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
    exit;
}
