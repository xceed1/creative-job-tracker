<?php
require_once "../auth/guard.php";
requireRole(['TRAFFIC']);
require_once "../config/db.php";

/* ============================================================
   INPUT
============================================================ */
$jobId = (int)($_GET['id'] ?? 0);
if ($jobId <= 0) {
    die("Invalid job");
}

/* ============================================================
   LOAD JOB (AUTHORITATIVE)
============================================================ */
$jobStmt = $pdo->prepare("
    SELECT
        jo.*,
        js.status_code,
        js.status_name,
        d.dept_name,
        u.full_name AS requester
    FROM job_orders jo
    JOIN job_status js ON jo.status_id = js.id
    JOIN departments d ON jo.requesting_department_id = d.id
    JOIN users u ON jo.created_by = u.id
    WHERE jo.id = ?
");
$jobStmt->execute([$jobId]);
$job = $jobStmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    die("Job not found");
}

/* ============================================================
   LOAD ASSIGNMENTS
============================================================ */
$assignStmt = $pdo->prepare("
    SELECT
        ja.*,
        u.full_name AS designer_name
    FROM job_assignments ja
    JOIN users u ON ja.designer_id = u.id
    WHERE ja.job_id = ?
    ORDER BY ja.assigned_at ASC
");
$assignStmt->execute([$jobId]);
$assignments = $assignStmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   AUTHORITATIVE COUNTS (SINGLE SOURCE OF TRUTH)
============================================================ */
$countStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(assignment_status = 'APPROVED') AS approved
    FROM job_assignments
    WHERE job_id = ?
");
$countStmt->execute([$jobId]);
$counts = $countStmt->fetch(PDO::FETCH_ASSOC);

$totalAssignments    = (int)$counts['total'];
$approvedAssignments = (int)$counts['approved'];

/* ============================================================
   FINAL TRUTH (SINGLE SOURCE OF TRUTH)
============================================================ */

// Allow approving the job only when:
// - at least 1 assignment exists
// - all assignments are approved
// - job is currently COMPLETED
$canApproveJob =
    $totalAssignments > 0 &&
    $approvedAssignments === $totalAssignments &&
    $job['status_code'] === 'COMPLETED';

// Allow release only when:
// - at least 1 assignment exists
// - all assignments are approved
// - job is currently APPROVED
$canRelease =
    $totalAssignments > 0 &&
    $approvedAssignments === $totalAssignments &&
    $job['status_code'] === 'APPROVED';

// Dispatch allowed unless released
$canDispatch = ($job['status_code'] !== 'RELEASED');


/* ============================================================
   DESIGNERS (FOR DISPATCH)
============================================================ */
$designers = $pdo->query("
    SELECT id, full_name
    FROM users
    WHERE role_id = (SELECT id FROM roles WHERE role_code = 'DESIGNER')
      AND is_active = 1
    ORDER BY full_name
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($job['job_code']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dual-list {
            min-height: 220px;
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <span class="navbar-brand fw-semibold">Creative Job Tracker</span>
            <a href="../auth/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </nav>

    <div class="container py-4">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-3">⬅ Back</a>

<!-- ============================================================
   JOB HEADER
============================================================ -->
        <div class="card shadow-sm mb-3">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <h4 class="mb-1"><?= htmlspecialchars($job['job_code']) ?></h4>
                    <div class="text-muted small mb-1">
                        <?= htmlspecialchars($job['dept_name']) ?> — <?= htmlspecialchars($job['requester']) ?>
                    </div>
                    <div class="small">
                        <strong>Project:</strong> <?= htmlspecialchars($job['project_name']) ?><br>
                        <strong>Subject:</strong> <?= htmlspecialchars($job['job_subject']) ?>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-primary mb-1"><?= htmlspecialchars($job['status_name']) ?></span>
                    <div class="small text-muted">Due: <?= htmlspecialchars($job['due_date']) ?></div>
                </div>
            </div>
        </div>

        <div class="small text-muted">
            DEBUG: status=<?= htmlspecialchars($job['status_code']) ?> |
            total=<?= (int)$totalAssignments ?> |
            approved=<?= (int)$approvedAssignments ?>
        </div>

        <!-- ============================================================
   BRIEF
============================================================ -->
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6 class="text-muted">Brief</h6>
                <div style="white-space:pre-line"><?= htmlspecialchars($job['brief']) ?></div>

                <?php if ($job['reference_link']): ?>
                    <div class="mt-3">
                        <strong>Reference:</strong>
                        <a href="<?= htmlspecialchars($job['reference_link']) ?>" target="_blank">
                            <?= htmlspecialchars($job['reference_link']) ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============================================================
   ASSIGNMENTS
============================================================ -->
        <?php foreach ($assignments as $a): ?>
            <div class="card shadow-sm mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong><?= htmlspecialchars($a['designer_name']) ?></strong>
                        <span class="badge bg-secondary"><?= htmlspecialchars($a['assignment_status']) ?></span>
                    </div>

                    <?php if (!empty($a['completion_link'])): ?>
                        <div class="alert alert-success mt-2">
                            <a href="<?= htmlspecialchars($a['completion_link']) ?>" target="_blank" rel="noopener">
                                View submission
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($a['assignment_status'] === 'COMPLETED'): ?>
                        <button class="btn btn-sm btn-success mt-2"
                            onclick="approveAssignment(<?= (int)$a['id'] ?>)">
                            Approve Assignment
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($canRelease): ?>
            <div class="card shadow-sm border-success mt-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">Ready for Release</h5>
                        <div class="text-muted small">
                            Job is approved. Release it to requester now.
                        </div>
                    </div>

                    <button class="btn btn-success" onclick="releaseJob()">
                        Release Job
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- ============================================================
   DISPATCH (MUST APPEAR ONCE ONLY — OUTSIDE LOOP)
============================================================ -->
        <?php if ($canDispatch): ?>
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h5>Dispatch Designers</h5>

                    <div class="row">
                        <div class="col-md-5">
                            <label class="small text-muted">Available Designers</label>
                            <select id="available" class="form-select dual-list" multiple size="10">
                                <?php foreach ($designers as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>">
                                        <?= htmlspecialchars($d['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex flex-column justify-content-center gap-2">
                            <button type="button" class="btn btn-outline-primary" onclick="move('available','selected')">→</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="move('selected','available')">←</button>
                        </div>

                        <div class="col-md-5">
                            <label class="small text-muted">Selected Designers</label>
                            <select id="selected" class="form-select dual-list" multiple size="10"></select>
                        </div>
                    </div>

                    <input id="hours" type="number" step="0.5" min="0.5"
                        class="form-control mt-3" placeholder="Estimated hours">
                    <textarea id="note" class="form-control mt-2" placeholder="Traffic note"></textarea>

                    <button type="button" class="btn btn-primary mt-3" onclick="dispatchMulti()">Dispatch</button>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script>
        function move(from, to) {
            const f = document.getElementById(from),
                t = document.getElementById(to);
            [...f.selectedOptions].forEach(o => t.appendChild(o));
        }





        function approveAssignment(assignmentId) {
            fetch('assignment_approve_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        assignment_id: assignmentId
                    })
                })
                .then(async r => {
                    const text = await r.text();
                    let json;
                    try {
                        json = JSON.parse(text);
                    } catch (e) {
                        console.error('Non-JSON response:', text);
                        alert('Approve API returned non-JSON (check PHP error output).');
                        return null;
                    }
                    return json;
                })
                .then(res => {
                    if (!res) return;
                    if (!res.success) {
                        console.error(res);
                        alert('Approve failed: ' + (res.error || 'Unknown error'));
                        return;
                    }
                    location.reload();
                })
                .catch(err => {
                    console.error(err);
                    alert('Network/JS error during approve.');
                });
        }
        function releaseJob() {
            if (!confirm('Release job to requester?')) return;
            fetch('decision_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    job_id: <?= $jobId ?>,
                    action: 'release'
                })
            }).then(r => r.json()).then(r => {
                if (!r.success) {
                    alert(r.error);
                    return;
                }
                location.reload();
            });
        }

        function dispatchMulti() {
            const ids = [...document.getElementById('selected').options].map(o => o.value);
            const hours = document.getElementById('hours').value;
            const note = document.getElementById('note').value;
            if (!ids.length || !hours) {
                alert('Designer(s) and hours required');
                return;
            }

            Promise.all(ids.map(id =>
                fetch('dispatch_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        job_id: <?= $jobId ?>,
                        designer_id: id,
                        estimated_hours: hours,
                        traffic_note: note
                    })
                })
            )).then(() => location.reload());
        }
    </script>

</body>

</html>