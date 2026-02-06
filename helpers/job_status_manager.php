<?php
// File: helpers/job_status_manager.php

declare(strict_types=1);

/**
 * CANONICAL JOB STATUS TRANSITION MANAGER
 * -------------------------------------
 * - Moves job status forward ONLY
 * - Enforces sequence integrity
 * - Relies on DB triggers for role validation
 * - Does NOT start transactions
 * - Does NOT touch session
 * - Does NOT output anything
 */

function transitionJobStatus(
    PDO $pdo,
    int $jobId,
    string $toStatusCode,
    string $triggeredBy,
    ?int $triggeredByUserId = null
): void {

    $toStatusCode = strtoupper(trim($toStatusCode));

    /* ============================================================
       LOCK JOB + CURRENT STATUS
    ============================================================ */
    $stmt = $pdo->prepare("
        SELECT
            jo.status_id,
            jo.is_locked,
            js.status_code,
            js.sequence
        FROM job_orders jo
        JOIN job_status js ON js.id = jo.status_id
        WHERE jo.id = ?
        FOR UPDATE
    ");
    $stmt->execute([$jobId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current) {
        throw new RuntimeException('Job not found');
    }

    if ((int)$current['is_locked'] === 1 && $current['status_code'] !== $toStatusCode) {
        throw new RuntimeException('Job is locked');
    }

    $currentSeq = (int)$current['sequence'];

    /* ============================================================
       RESOLVE TARGET STATUS
    ============================================================ */
    $stmt = $pdo->prepare("
        SELECT id, sequence
        FROM job_status
        WHERE status_code = ?
        LIMIT 1
    ");
    $stmt->execute([$toStatusCode]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target) {
        throw new RuntimeException('Invalid target status');
    }

    $targetSeq = (int)$target['sequence'];

    if ($targetSeq <= $currentSeq) {
        // backward or same → ignore silently
        return;
    }

    /* ============================================================
       LOAD FORWARD PATH (SEQUENCE-SAFE)
    ============================================================ */
    $stmt = $pdo->prepare("
        SELECT id, status_code, sequence
        FROM job_status
        WHERE sequence BETWEEN ? AND ?
        ORDER BY sequence ASC
    ");
    $stmt->execute([$currentSeq + 1, $targetSeq]);
    $path = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ============================================================
       WALK STATUS STEP BY STEP
    ============================================================ */
    foreach ($path as $step) {

        $nextId   = (int)$step['id'];
        $nextCode = (string)$step['status_code'];

        /* ---------------- BUSINESS GUARDS ---------------- */

        if ($nextCode === 'COMPLETED') {
            $c = $pdo->prepare("
                SELECT COUNT(*)
                FROM job_assignments
                WHERE job_id = ?
                  AND assignment_status NOT IN ('COMPLETED','APPROVED')
            ");
            $c->execute([$jobId]);
            if ((int)$c->fetchColumn() !== 0) {
                throw new RuntimeException('Cannot move to COMPLETED: pending assignments');
            }
        }

        if ($nextCode === 'APPROVED') {
            $c = $pdo->prepare("
                SELECT COUNT(*)
                FROM job_assignments
                WHERE job_id = ?
                  AND assignment_status <> 'APPROVED'
            ");
            $c->execute([$jobId]);
            if ((int)$c->fetchColumn() !== 0) {
                throw new RuntimeException('Cannot move to APPROVED: pending approvals');
            }
        }

        /* ---------------- APPLY STATUS ---------------- */

        $lock = ($nextCode === 'RELEASED') ? 1 : 0;

        $u = $pdo->prepare("
            UPDATE job_orders
            SET
                status_id = ?,
                is_locked = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $u->execute([$nextId, $lock, $jobId]);
    }
}
