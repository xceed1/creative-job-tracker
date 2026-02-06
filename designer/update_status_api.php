<?php
// File: designer/update_status_api.php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
ob_start();

require_once "../auth/guard.php";
requireRole(['DESIGNER']);
require_once "../config/db.php";

require_once "../config/db_context.php";
setDbContext($pdo, 'DESIGNER');

require_once "../helpers/job_status_manager.php";

header('Content-Type: application/json; charset=utf-8');

try {

    $assignmentId = (int)($_POST['assignment_id'] ?? 0);
    $action       = strtolower(trim((string)($_POST['action'] ?? '')));
    $designerId   = (int)($_SESSION['user']['id'] ?? 0);

    if ($assignmentId <= 0 || $designerId <= 0 || $action === '') {
        throw new Exception('Invalid request');
    }

    $pdo->beginTransaction();

    /* ============================================================
       LOCK ASSIGNMENT + JOB (SAME TRANSACTION)
    ============================================================ */
    $stmt = $pdo->prepare("
        SELECT
            ja.id,
            ja.job_id,
            ja.assignment_status,
            ja.assigned_due_at,
            jo.status_id,
            js.status_code AS job_status_code
        FROM job_assignments ja
        JOIN job_orders jo ON jo.id = ja.job_id
        JOIN job_status js ON js.id = jo.status_id
        WHERE ja.id = ?
          AND ja.designer_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$assignmentId, $designerId]);
    $a = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$a) {
        throw new Exception('Assignment not found');
    }

    $jobId = (int)$a['job_id'];

    /* ============================================================
       START TASK
    ============================================================ */
    if ($action === 'start') {

        if (!in_array($a['assignment_status'], ['ASSIGNED', 'REJECTED'], true)) {
            throw new Exception('Invalid assignment state');
        }

        $stmt = $pdo->prepare("
            UPDATE job_assignments
            SET assignment_status = 'IN_PROGRESS'
            WHERE id = ?
              AND assignment_status IN ('ASSIGNED','REJECTED')
        ");
        $stmt->execute([$assignmentId]);

        // Move job forward safely
        transitionJobStatus(
            $pdo,
            $jobId,
            'IN_PROGRESS',
            'DESIGNER',
            $designerId
        );

        $pdo->commit();
        ob_clean();
        echo json_encode(['success' => true]);
        exit;
    }

    /* ============================================================
       COMPLETE TASK
    ============================================================ */
    if ($action === 'complete') {

        if ($a['assignment_status'] !== 'IN_PROGRESS') {
            throw new Exception('Assignment not in progress');
        }

        $link = trim((string)($_POST['completion_link'] ?? ''));
        if ($link === '') {
            throw new Exception('Completion link required');
        }

        // 1️⃣ Mark assignment completed
        $stmt = $pdo->prepare("
            UPDATE job_assignments
            SET
                assignment_status = 'COMPLETED',
                completion_link   = ?,
                completed_at      = NOW(),
                sla_minutes_delta = TIMESTAMPDIFF(MINUTE, assigned_due_at, NOW()),
                sla_breached      = IF(TIMESTAMPDIFF(MINUTE, assigned_due_at, NOW()) < 0, 1, 0)
            WHERE id = ?
              AND assignment_status = 'IN_PROGRESS'
        ");
        $stmt->execute([$link, $assignmentId]);

        // 2️⃣ If NO active assignments remain → job COMPLETED
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM job_assignments
            WHERE job_id = ?
              AND assignment_status IN ('ASSIGNED','IN_PROGRESS')
        ");
        $stmt->execute([$jobId]);

        if ((int)$stmt->fetchColumn() === 0) {
            transitionJobStatus(
                $pdo,
                $jobId,
                'COMPLETED',
                'DESIGNER',
                $designerId
            );
        }

        $pdo->commit();
        ob_clean();
        echo json_encode(['success' => true]);
        exit;
    }

    throw new Exception('Unknown action');

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('[DESIGNER UPDATE ERROR] ' . $e->getMessage());

    ob_clean();
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
    exit;
}