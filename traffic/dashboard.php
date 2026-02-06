<?php
require_once "../auth/guard.php";
requireRole(['TRAFFIC']);

/* ============================================================
| DB GUARD — PREVENT 503 WHITE SCREENS
============================================================ */
try {
    require_once "../config/db.php";
} catch (Throwable $e) {

    error_log('[TRAFFIC DASHBOARD DB ERROR] ' . $e->getMessage());
    http_response_code(503);

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Service Unavailable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <div class="alert alert-danger text-center shadow-sm">
        <h5 class="mb-2">System temporarily unavailable</h5>
        <p class="mb-0">Please try again in a few moments.</p>
    </div>
</div>
</body>
</html>
HTML;
    exit;
}

/* ============================================================
| LOAD JOBS (DB TIME IS SOURCE OF TRUTH)
| SLA LOGIC:
| - IN PROGRESS: NOW() vs assigned_due_at
| - COMPLETED / RELEASED: completed_at vs assigned_due_at (FROZEN)
============================================================ */
$jobs = $pdo->query("
    SELECT 
        jo.id,
        jo.job_code,
        jo.project_name,
        jo.job_subject,
        jo.created_at,
        jo.is_locked,
        js.status_code,
        js.status_name,

        d.dept_name,
        u.full_name AS requester,

        des.full_name AS designer_name,
        ja.estimated_hours,
        ja.assigned_due_at,
        ja.completion_link,
        ja.completed_at,

        CASE
            WHEN ja.completed_at IS NOT NULL
                THEN TIMESTAMPDIFF(MINUTE, ja.completed_at, ja.assigned_due_at)
            ELSE
                TIMESTAMPDIFF(MINUTE, NOW(), ja.assigned_due_at)
        END AS sla_minutes_left

    FROM job_orders jo
    JOIN job_status js ON jo.status_id = js.id
    JOIN departments d ON jo.requesting_department_id = d.id
    JOIN users u ON jo.created_by = u.id
    LEFT JOIN job_assignments ja ON ja.job_id = jo.id
    LEFT JOIN users des ON ja.designer_id = des.id
    ORDER BY jo.created_at DESC
")->fetchAll();

/* ============================================================
| SLA CONFIG — MUST MATCH job_view.php
============================================================ */
$SLA_WARNING_MINUTES = 60;
function formatSlaMinutes(int $minutes): string
{
    $abs = abs($minutes);

    if ($abs === 1) {
        return '1 min';
    }

    return $abs . ' mins';
}

/* ============================================================
| STATUS COLOR MAP (UNCHANGED)
============================================================ */
function statusColor(string $status): string
{
    return match ($status) {
        'NEW' => 'primary',
        'DISPATCHED', 'IN_PROGRESS' => 'warning',
        'COMPLETED' => 'danger',
        'APPROVED', 'RELEASED' => 'success',
        'REJECTED' => 'secondary',
        default => 'secondary'
    };
}

/* ============================================================
| SUMMARY COUNTS (UNCHANGED)
============================================================ */
$countNew        = count(array_filter($jobs, fn($j) => $j['status_code'] === 'NEW'));
$countInProgress = count(array_filter($jobs, fn($j) => in_array($j['status_code'], ['DISPATCHED', 'IN_PROGRESS'], true)));
$countCompleted  = count(array_filter($jobs, fn($j) => $j['status_code'] === 'COMPLETED'));
$countReleased   = count(array_filter($jobs, fn($j) => $j['status_code'] === 'RELEASED'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Traffic Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .job-row>td {
            background: #fff !important;
            vertical-align: middle;
        }

        .job-row.alt>td {
            background: #f5f5f5 !important;
        }

        .job-row.released>td {
            opacity: .6;
        }

        .job-row.completed>td {
            background: #fff3cd !important;
        }

        .job-actions {
            text-align: center;
            white-space: nowrap;
        }

        .job-actions .btn {
            min-width: 120px;
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <span class="navbar-brand fw-semibold">Creative Job Tracker</span>
            <div class="d-flex gap-3 align-items-center">
                <span class="text-light small">Traffic Manager</span>
                <a href="../auth/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-3">
        <h4 class="mb-3">Traffic Dashboard</h4>

        <div class="sticky-top bg-light pt-2" style="z-index:1020;">
            <div class="card shadow-sm mb-3">
                <div class="card-body d-flex justify-content-between text-center">
                    <div class="flex-fill">
                        <div class="text-muted small">New</div>
                        <div class="fw-bold"><?= $countNew ?></div>
                    </div>
                    <div class="flex-fill">
                        <div class="text-muted small">In Progress</div>
                        <div class="fw-bold"><?= $countInProgress ?></div>
                    </div>
                    <div class="flex-fill">
                        <div class="text-muted small">Completed</div>
                        <div class="fw-bold text-danger"><?= $countCompleted ?></div>
                    </div>
                    <div class="flex-fill">
                        <div class="text-muted small">Released</div>
                        <div class="fw-bold"><?= $countReleased ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive" style="max-height:calc(100vh - 260px);overflow-y:auto;">
                <table class="table align-middle mb-0">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th>Job Code</th>
                            <th>Project</th>
                            <th>Requester</th>
                            <th>Received</th>
                            <th>Assignment</th>
                            <th>SLA</th>
                            <th>Status</th>
                            <th style="width:160px">Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php foreach ($jobs as $i => $job): ?>
                            <?php
                            $row = ['job-row'];
                            if ($i % 2) $row[] = 'alt';
                            if ($job['status_code'] === 'RELEASED') $row[] = 'released';
                            if ($job['status_code'] === 'COMPLETED') $row[] = 'completed';

                            // ============================================================
                            // SLA RESOLUTION — CANONICAL (DO NOT REORDER)
                            // ============================================================

                            $slaLabel = null;
                            $slaClass = null;

                            $minutes = $job['sla_minutes_left'] !== null
                                ? (int)$job['sla_minutes_left']
                                : null;

                            if (
                                in_array($job['status_code'], ['RELEASED', 'APPROVED'], true)
                                && $minutes !== null
                            ) {
                                // ================= COMPLETED / RELEASED (FROZEN) =================

                                if ($minutes >= 0) {
                                    $slaLabel = 'Completed on time (' . formatSlaMinutes($minutes) . ' early)';
                                    $slaClass = 'bg-success';
                                } else {
                                    $slaLabel = 'Completed late (' . formatSlaMinutes($minutes) . ' late)';
                                    $slaClass = 'bg-danger';
                                }
                            } elseif (
                                in_array($job['status_code'], ['DISPATCHED', 'IN_PROGRESS'], true)
                                && $minutes !== null
                            ) {
                                // ================= IN PROGRESS (LIVE) =================

                                if ($minutes < 0) {
                                    $slaLabel = 'Overdue (' . formatSlaMinutes($minutes) . ' late)';
                                    $slaClass = 'bg-danger';
                                } elseif ($minutes <= $SLA_WARNING_MINUTES) {
                                    $slaLabel = 'Due Soon (' . formatSlaMinutes($minutes) . ' left)';
                                    $slaClass = 'bg-warning text-dark';
                                } else {
                                    $slaLabel = 'On Track (' . formatSlaMinutes($minutes) . ' left)';
                                    $slaClass = 'bg-success';
                                }
                            }

                            ?>
                            <tr class="<?= implode(' ', $row) ?>">
                                <td class="fw-semibold"><?= htmlspecialchars($job['job_code']) ?></td>
                                <td><?= htmlspecialchars($job['project_name']) ?><br><small class="text-muted"><?= htmlspecialchars($job['job_subject']) ?></small></td>
                                <td><?= htmlspecialchars($job['dept_name']) ?><br><small class="text-muted">by <?= htmlspecialchars($job['requester']) ?></small></td>
                                <td><?= date('Y-m-d H:i', strtotime($job['created_at'])) ?></td>
                                <td>
                                    <?php if ($job['designer_name']): ?>
                                        <strong><?= htmlspecialchars($job['designer_name']) ?></strong><br>
                                        <small class="text-muted"><?= $job['estimated_hours'] ?> hrs<br>Due: <?= $job['assigned_due_at'] ?></small>
                                    <?php else: ?><span class="text-muted">Not dispatched</span><?php endif; ?>
                                </td>
                                <td><?= $slaLabel ? "<span class='badge $slaClass'>$slaLabel</span>" : "<span class='text-muted'>—</span>" ?></td>
                                <td><span class="badge bg-<?= statusColor($job['status_code']) ?>"><?= htmlspecialchars($job['status_name']) ?></span></td>
                                <td class="job-actions">
                                    <a href="job_view.php?id=<?= (int)$job['id'] ?>" class="btn btn-sm btn-primary">Manage</a>
                                    <?php if ($job['status_code'] === 'COMPLETED'): ?><div class="small text-danger mt-1">Action required</div><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>