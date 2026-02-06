<?php

/**
 * Generate Job Code (PRODUCTION SAFE)
 * ----------------------------------
 * - Uses MySQL table locking (no transactions)
 * - Supports global + department prefixes
 * - Prevents race conditions
 */

function generateJobCode(PDO $pdo, ?int $departmentId = null): string
{
    $year = date('Y');

    try {

        /* ---------------------------------
        | LOCK REQUIRED TABLES
        | (MySQL auto-commit safe)
        ----------------------------------*/
        $pdo->exec("
            LOCK TABLES 
                job_orders WRITE,
                system_settings READ
        ");

        /* ---------------------------------
        | RESOLVE PREFIX
        ----------------------------------*/
        $prefix = null;

        // 1️⃣ Department-specific prefix
        if ($departmentId) {
            $stmt = $pdo->prepare("
                SELECT setting_value
                FROM system_settings
                WHERE setting_key = ?
                LIMIT 1
            ");
            $stmt->execute(['JOB_CODE_PREFIX_DEPT_' . $departmentId]);
            $prefix = $stmt->fetchColumn();
        }

        // 2️⃣ Global prefix fallback
        if (!$prefix) {
            $stmt = $pdo->prepare("
                SELECT setting_value
                FROM system_settings
                WHERE setting_key = 'JOB_CODE_PREFIX'
                LIMIT 1
            ");
            $stmt->execute();
            $prefix = $stmt->fetchColumn();
        }

        // 3️⃣ Hard fallback
        if (!$prefix) {
            $prefix = 'JOB';
        }

        /* ---------------------------------
        | CALCULATE NEXT SEQUENCE
        ----------------------------------*/
        $stmt = $pdo->prepare("
            SELECT MAX(id)
            FROM job_orders
            WHERE YEAR(created_at) = ?

        ");
        $stmt->execute([$year]);

        $sequence = ((int)$stmt->fetchColumn()) + 1;

        /* ---------------------------------
        | UNLOCK TABLES
        ----------------------------------*/
        $pdo->exec("UNLOCK TABLES");

        return sprintf(
            '%s-%s-%04d',
            strtoupper($prefix),
            $year,
            $sequence
        );

    } catch (Throwable $e) {

        // Always unlock if something breaks
        try {
            $pdo->exec("UNLOCK TABLES");
        } catch (Throwable $ignored) {}

        throw $e;
    }
}
