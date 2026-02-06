<?php

declare(strict_types=1);

/* =========================================================
| SILENT + SAFE
========================================================= */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
ob_start();

require_once "../auth/guard.php";
requireRole(['USER']);
require_once "../config/db.php";
require_once "../helpers/activity_logger.php";
require_once "../helpers/job_code.php";
require_once "../helpers/uuid.php";

header('Content-Type: application/json; charset=utf-8');

try {

    /* =========================================================
    | SESSION — SINGLE SOURCE OF TRUTH
    ========================================================= */
    if (
        empty($_SESSION['user']['id']) ||
        empty($_SESSION['user']['department_id'])
    ) {
        throw new Exception('Invalid user context');
    }

    $userId       = (int)$_SESSION['user']['id'];
    $departmentId = (int)$_SESSION['user']['department_id'];

    if ($departmentId <= 0) {
        throw new Exception('Your account is not linked to a department');
    }

    /* =========================================================
    | REQUIRED INPUTS
    ========================================================= */
    $clientName  = trim($_POST['client_name'] ?? '');
    $projectName = trim($_POST['project_name'] ?? '');
    $jobSubject  = trim($_POST['job_subject'] ?? '');
    $brief       = trim($_POST['brief'] ?? '');
    $dueDate     = $_POST['due_date'] ?? null;

    if (
        $clientName === '' ||
        $projectName === '' ||
        $jobSubject === '' ||
        $brief === '' ||
        !$dueDate
    ) {
        throw new Exception('Missing required fields');
    }

    /* =========================================================
    | OPTIONAL FIELDS
    ========================================================= */
    $receivedFormat = trim($_POST['received_format'] ?? '') ?: null;
    $receivedAt     = $_POST['received_at'] ?: null;
    $referenceLink  = $_POST['reference_link'] ?: null;


/* ---------------------------------
| FILE UPLOAD (UUID, ≤ 1MB)
----------------------------------*/
    $receivedFilePath = null;

    if (
        isset($_FILES['received_format_file']) &&
        $_FILES['received_format_file']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $file = $_FILES['received_format_file'];

        /* ---------- HARD FAILS ---------- */
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error code: ' . $file['error']);
        }

        if ($file['size'] > 1024 * 1024) {
            throw new Exception('Attachment exceeds 1MB limit');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid uploaded file');
        }

        /* ---------- PATH ---------- */
        $uploadDir = dirname(__DIR__) . '/uploads/received_formats/';

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0775, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }

        if (!is_writable($uploadDir)) {
            throw new Exception('Upload directory is not writable');
        }

        /* ---------- UUID FILENAME ---------- */
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext === '') {
            throw new Exception('Invalid file extension');
        }

        $uuid = bin2hex(random_bytes(16));
        $filename = $uuid . '.' . $ext;

        $destination = $uploadDir . $filename;

        /* ---------- MOVE ---------- */
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to move uploaded file');
        }

        /* ---------- STORE RELATIVE PATH ---------- */
        $receivedFilePath = 'uploads/received_formats/' . $filename;
    }



    /* =========================================================
    | GENERATE JOB CODE (ONCE, BEFORE INSERT)
    ========================================================= */
    $jobCode = generateJobCode($pdo, $departmentId);

    /* =========================================================
    | INSERT JOB (ATOMIC + FK SAFE)
    ========================================================= */
    $stmt = $pdo->prepare("
        INSERT INTO job_orders
        (
            job_code,
            client_name,
            project_name,
            job_subject,
            brief,
            due_date,
            received_format,
            received_format_file,
            received_at,
            reference_link,
            requesting_department_id,
            created_by,
            status_id,
            is_locked,
            created_at
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?,
            (SELECT id FROM job_status WHERE status_code = 'NEW'),
            0,
            NOW()
        )
    ");

    $stmt->execute([
        $jobCode,
        $clientName,
        $projectName,
        $jobSubject,
        $brief,
        $dueDate,
        $receivedFormat,
        $receivedFilePath,
        $receivedAt,
        $referenceLink,
        $departmentId,
        $userId
    ]);

    $jobId = (int)$pdo->lastInsertId();

    /* =========================================================
    | ACTIVITY LOG
    ========================================================= */
    logActivity(
        $pdo,
        $jobId,
        'USER_CREATE_JOB',
        [
            'job_code' => $jobCode,
            'client'   => $clientName
        ]
    );

    ob_clean();
    echo json_encode([
        'success'  => true,
        'job_id'   => $jobId,
        'job_code' => $jobCode
    ]);
    exit;
} catch (Throwable $e) {

    error_log('[CREATE JOB ERROR] ' . $e->getMessage());

    ob_clean();
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
    exit;
}