<?php

declare(strict_types=1);

/* ---------- SILENT MODE ---------- */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

ob_start();

require_once "../auth/guard.php";
requireRole(['USER']);
require_once "../config/db.php";
require_once "../config/db_context.php";
setDbContext($pdo);
require_once "../helpers/activity_logger.php";
require_once "../config/paths.php";

header('Content-Type: application/json; charset=utf-8');

try {

    $jobId  = (int)($_POST['job_id'] ?? 0);
    $userId = (int)($_SESSION['user']['id'] ?? 0);

    if ($jobId <= 0 || $userId <= 0) {
        throw new Exception('Invalid request');
    }

    /* ---------------------------------
    | FETCH CURRENT JOB (EDITABLE ONLY)
    ----------------------------------*/
    $stmt = $pdo->prepare("
SELECT
        project_name,
        client_name,
        received_format,
        received_format_file,
        received_at,
        job_subject,
        brief,
        due_date,
        reference_link
        FROM job_orders
        WHERE id = ?
          AND created_by = ?
          AND is_locked = 0
    ");
    $stmt->execute([$jobId, $userId]);
    $before = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$before) {
        throw new Exception('Job not editable');
    }

    /* ---------------------------------
    | PREVENT RECEIVED INFO WIPE
    ----------------------------------*/
    if (
        $before['client_name'] !== null &&
        (
            trim($_POST['client_name'] ?? '') === '' ||
            trim($_POST['received_format'] ?? '') === '' ||
            empty($_POST['received_at'])
        )
    ) {
        throw new Exception('Received info cannot be cleared once set');
    }

    require_once "../helpers/uuid.php";

    /* ---------------------------------
| FILE UPLOAD (UUID, ≤ 1MB) — UPDATE
----------------------------------*/
    $receivedFilePath = $before['received_format_file'];

    if (
        isset($_FILES['received_format_file']) &&
        $_FILES['received_format_file']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $file = $_FILES['received_format_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error code: ' . $file['error']);
        }

        if ($file['size'] > 1024 * 1024) {
            throw new Exception('Attachment exceeds 1MB limit');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid uploaded file');
        }

        $uploadDir = dirname(__DIR__) . '/uploads/received_formats/';

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0775, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }

        // Ensure directory exists (this check IS reliable)
        if (!is_dir($uploadDir)) {
            throw new Exception('Upload directory does not exist');
        }

        // DO NOT trust is_writable() on Windows
        // Attempt the move and handle failure instead


        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === '') {
            throw new Exception('Invalid file extension');
        }

        $uuid = bin2hex(random_bytes(16));
        $filename = $uuid . '.' . $ext;
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to move uploaded file');
        }

        // Mark old file for deletion AFTER successful update
        if (!empty($before['received_format_file'])) {
            $oldFileToDelete = dirname(__DIR__) . '/' . $before['received_format_file'];
        }

        $receivedFilePath = 'uploads/received_formats/' . $filename;
    }

    /* ---------------------------------
    | NORMALIZE REFERENCE LINK (SAFE)
    ----------------------------------*/
    $referenceLink = trim($_POST['reference_link'] ?? '');

    if ($referenceLink !== '' && !filter_var($referenceLink, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid reference link');
    }

    $referenceLink = $referenceLink !== '' ? $referenceLink : null;

    $after = [
        'project_name'         => trim($_POST['project_name']),
        'client_name'          => trim($_POST['client_name']),
        'received_format'      => trim($_POST['received_format']),
        'received_format_file' => $receivedFilePath,
        'received_at'          => !empty($_POST['received_at']) ? $_POST['received_at'] : null,
        'job_subject'          => trim($_POST['job_subject']),
        'brief'                => trim($_POST['brief']),
        'due_date'             => $_POST['due_date'],
        'reference_link'       => $referenceLink
    ];



    /* ---------------------------------
    | UPDATE
    ----------------------------------*/
    $stmt = $pdo->prepare("
    UPDATE job_orders
    SET
        project_name = ?,
        client_name = ?,
        received_format = ?,
        received_format_file = ?,
        received_at = ?,
        job_subject = ?,
        brief = ?,
        due_date = ?,
        reference_link = ?,
        updated_at = NOW()
    WHERE id = ?
        AND created_by = ?
        AND is_locked = 0
    ");

    $stmt->execute([
        $after['project_name'],
        $after['client_name'],
        $after['received_format'],
        $after['received_format_file'],
        $after['received_at'],
        $after['job_subject'],
        $after['brief'],
        $after['due_date'],
        $after['reference_link'],
        $jobId,
        $userId
    ]);

    /* ---------------------------------
| DELETE OLD FILE (POST-UPDATE)
----------------------------------*/
    if ($oldFileToDelete && is_file($oldFileToDelete)) {
        @unlink($oldFileToDelete);
    }

    /* ---------------------------------
    | ACTIVITY LOG (DIFF ONLY)
    ----------------------------------*/
    if ($before != $after) {
        logActivity(
            $pdo,
            $jobId,
            'USER_EDIT_JOB',
            [
                'before' => $before,
                'after'  => $after
            ]
        );
    }

    ob_clean();
    echo json_encode([
        'success'     => true,
        'job_subject' => $after['job_subject']
    ]);
    exit;
} catch (Throwable $e) {

    error_log('[USER UPDATE ERROR] ' . $e->getMessage());

    ob_clean();
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
    exit;
}
