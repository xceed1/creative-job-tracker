<?php
require_once "../auth/guard.php";
requireRole(['USER']);
require_once "../config/db.php";

$userId = $_SESSION['user']['id'];

$search = trim($_GET['q'] ?? '');
$from = !empty($_GET['from'])
    ? $_GET['from']
    : date('Y-m-01');

$to = !empty($_GET['to'])
    ? $_GET['to']
    : date('Y-m-t');


/* ============================================================
   QUERY (UNCHANGED LOGIC)
============================================================ */
$sql = "
    SELECT 
        jo.*,
        js.status_code,
        GROUP_CONCAT(DISTINCT ja.completion_link SEPARATOR '\n') AS completion_links,
        GROUP_CONCAT(DISTINCT d.full_name SEPARATOR ', ') AS designer_names
    FROM job_orders jo
        LEFT JOIN job_status js ON jo.status_id = js.id
        LEFT JOIN job_assignments ja ON ja.job_id = jo.id
        LEFT JOIN users d ON ja.designer_id = d.id
    WHERE jo.created_by = ?
      AND COALESCE(jo.received_at, jo.created_at)
            BETWEEN CONCAT(?, ' 00:00:00')
                AND CONCAT(?, ' 23:59:59')
";

$params = [$userId, $from, $to];

if ($search !== '') {
    $sql .= "
        AND (
            jo.job_code LIKE ?
            OR jo.job_subject LIKE ?
            OR jo.project_name LIKE ?
        )
    ";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}

$sql .= "
    GROUP BY jo.id
    ORDER BY jo.created_at DESC
";


$jobsStmt = $pdo->prepare($sql);
$jobsStmt->execute($params);
$jobs = $jobsStmt->fetchAll(PDO::FETCH_ASSOC);

function userStatusLabel(string $code): array
{
    return match ($code) {
        'NEW' => ['Received', 'primary'],
        'DISPATCHED', 'IN_PROGRESS', 'COMPLETED', 'REJECTED' => ['In Process', 'warning'],
        'RELEASED' => ['Released', 'success'],
        default => ['In Process', 'secondary']
    };
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>My Job Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* ===== User Dashboard Job Table Styling (BOOTSTRAP-SAFE) ===== */

        /* Base job row cells */
        .job-row>td {
            background-color: #ffffff !important;
        }

        /* Alternating rows */
        .job-row.alt>td {
            background-color: #daf4f9 !important;
            /* light cyan */
        }

        /* Hover (overrides both)
        .job-row:hover>td {
            background-color: #e3f6fa !important;
            transition: background-color 0.15s ease-in-out;
        } */

        /* Expanded detail rows */
        .job-detail>td {
            background-color: #ffffff !important;
        }

        /* Keep text aligned nicely */
        .job-table td {
            vertical-align: middle;
        }

        .job-detail {
            background-color: #ffffff;
        }

        .job-detail .small {
            font-size: 0.75rem;
            letter-spacing: 0.03em;
        }

        .job-detail .fw-semibold {
            font-size: 0.95rem;
        }

        .job-actions {
            vertical-align: middle !important;
            white-space: nowrap;
        }

        .job-actions .btn {
            min-height: 32px;
        }
    </style>
</head>

<body class="bg-light">

    <!-- ================= NAVBAR ================= -->
    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <span class="navbar-brand fw-semibold">Creative Job Tracker</span>

            <div class="d-flex align-items-center gap-3">
                <span class="text-light small">
                    <?= htmlspecialchars($_SESSION['user']['name'] ?? 'User') ?>
                </span>
                <a href="../auth/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <!-- ================= CONTENT ================= -->
    <div class="container-fluid px-4 py-3">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">My Job Requests</h4>
            <button class="btn btn-primary" onclick="openCreateJob()">+ New Job</button>
        </div>

        <!-- ============================================================
         FILTER BAR (ALWAYS VISIBLE — ADDED UX FIX)
    ============================================================ -->
        <div class="sticky-top bg-light pt-2" style="z-index:1020;">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <form class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Search</label>
                            <input type="text" name="q" class="form-control"
                                placeholder="Job code, subject, project…"
                                value="<?= htmlspecialchars($search) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small text-muted">From</label>
                            <input type="date" name="from" class="form-control"
                                value="<?= htmlspecialchars($from) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small text-muted">To</label>
                            <input type="date" name="to" class="form-control"
                                value="<?= htmlspecialchars($to) ?>">
                        </div>

                        <div class="col-md-2 d-flex gap-2">
                            <button class="btn btn-primary w-100">Apply</button>
                            <a href="dashboard.php" class="btn btn-outline-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============================================================
         TABLE CARD
    ============================================================ -->
        <div class="card shadow-sm">

            <!-- SCROLL CONTAINER -->
            <div class="table-responsive"
                style="max-height: calc(100vh - 260px); overflow-y:auto;">
                <!-- ===== TABLE META INFO ===== -->
                <div class="px-3 py-2 border-bottom bg-white small text-muted d-flex justify-content-between align-items-center">

                    <div>
                        <strong><?= count($jobs) ?></strong>
                        job<?= count($jobs) !== 1 ? 's' : '' ?> found
                    </div>

                    <div>
                        <span class="me-2">
                            <strong>From:</strong> <?= htmlspecialchars($from) ?>
                        </span>
                        <span>
                            <strong>To:</strong> <?= htmlspecialchars($to) ?>
                        </span>
                    </div>

                </div>

                <table class="table align-middle mb-0 job-table">

                    <!-- FIXED HEADER (NO job-row CLASS HERE ❗) -->
                    <thead class="table-dark border-bottom"
                        style="position: sticky; top: 0; z-index: 1010;">
                        <tr class="text-white fw-semibold">
                            <th style="width:140px">Job Code</th>
                            <th>Client</th>
                            <th>Project</th>
                            <th>Job Subject</th>
                            <th style="width:160px">Received At</th>
                            <th style="width:120px">Due</th>
                            <th style="width:180px">Assigned Designer</th>
                            <th style="width:140px">Status</th>
                            <th style="width:220px">Actions</th>
                        </tr>
                    </thead>


                    <tbody>

                        <?php if (!$jobs): ?>
                            <!-- EMPTY STATE (NO job-row CLASS ❗) -->
                            <tr>
                                <td colspan="6">
                                    <div class="alert alert-info m-3">
                                        No jobs found for this period.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($jobs as $job): ?>
                            <?php [$label, $color] = userStatusLabel($job['status_code']); ?>

                            <!-- ✅ REAL JOB ROW (THIS IS WHAT GETS STRIPED) -->
                            <tr class="job-row">

                                <td class="fw-semibold">
                                    <?= htmlspecialchars($job['job_code']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($job['client_name'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($job['project_name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($job['job_subject']) ?>
                                </td>

                                <td>
                                    <?= $job['received_at']
                                        ? date('Y-m-d H:i', strtotime($job['received_at']))
                                        : '<span class="text-muted">—</span>' ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($job['due_date']) ?>
                                </td>

                                <td>
                                    <?php if (!empty($job['designer_names'])): ?>
                                        <?= htmlspecialchars($job['designer_names']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge bg-<?= $color ?>">
                                        <?= $label ?>
                                    </span>

                                    <?php if ($job['status_code'] === 'NEW' && empty($job['client_name'])): ?>
                                        <span class="badge bg-warning ms-1">Missing Info</span>
                                    <?php endif; ?>
                                </td>

                                <td class="job-actions align-middle">
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <button class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#view<?= $job['id'] ?>">
                                            View
                                        </button>

                                        <?php if ($job['status_code'] === 'NEW'): ?>
                                            <button class="btn btn-sm btn-warning"
                                                onclick='openEditJob(<?= json_encode($job, JSON_HEX_APOS) ?>)'>
                                                Edit
                                            </button>

                                            <button class="btn btn-sm btn-danger"
                                                onclick="deleteJob(<?= (int)$job['id'] ?>)">
                                                Delete
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small d-inline-flex align-items-center">
                                                🔒 Locked
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <!-- SLIDE-DOWN DETAILS (NO job-row CLASS ❗) -->
                            <tr class="collapse job-detail w-100" id="view<?= $job['id'] ?>">

                                <td colspan="9" class="p-0">
                                    <div class="px-4 py-3 bg-light border-top">

                                        <!-- TOP META GRID -->
                                        <div class="row g-3 mb-3">

                                            <div class="col-md-3">
                                                <div class="small text-muted">Job Code</div>
                                                <div class="fw-semibold"><?= htmlspecialchars($job['job_code']) ?></div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="small text-muted">Client</div>
                                                <div><?= htmlspecialchars($job['client_name'] ?? '-') ?></div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="small text-muted">Project</div>
                                                <div><?= htmlspecialchars($job['project_name']) ?></div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="small text-muted">Job Subject</div>
                                                <?= htmlspecialchars($job['job_subject']) ?>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="small text-muted">Received At</div>
                                            <div>
                                                <?= $job['received_at']
                                                    ? date('Y-m-d H:i', strtotime($job['received_at']))
                                                    : '—' ?>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="small text-muted">Due Date</div>
                                            <div><?= htmlspecialchars($job['due_date']) ?></div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="small text-muted">Assigned Designer(s)</div>
                                            <div>
                                                <?= !empty($job['designer_names'])
                                                    ? htmlspecialchars($job['designer_names'])
                                                    : '<span class="text-muted">Not assigned</span>' ?>

                                            </div>
                                        </div>

                                    </div>

                                    <!-- BRIEF -->
                                    <div class="mb-3">
                                        <div class="fw-semibold mb-1">Brief</div>
                                        <div class="text-muted" style="white-space: pre-line;">
                                            <?= htmlspecialchars($job['brief']) ?>
                                        </div>
                                    </div>

                                    <!-- REFERENCE -->
                                    <?php if ($job['reference_link']): ?>
                                        <div class="mb-3">
                                            <div class="fw-semibold mb-1">Reference</div>
                                            <a href="<?= htmlspecialchars($job['reference_link']) ?>"
                                                target="_blank" rel="noopener">
                                                <?= htmlspecialchars($job['reference_link']) ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <!-- COMPLETED WORK -->
                                    <?php if (
                                        $job['status_code'] === 'RELEASED'
                                        && !empty($job['completion_links'])
                                    ): ?>
                                        <div class="pt-3 border-top">
                                            <div class="fw-semibold mb-2">Completed Work</div>

                                            <?php foreach (explode("\n", $job['completion_links']) as $link): ?>
                                                <div>
                                                    <a href="<?= htmlspecialchars(trim($link)) ?>"
                                                        target="_blank"
                                                        rel="noopener">
                                                        View Final Output
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>


            </div>
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




<!-- ================= JOB MODAL ================= -->
<div class="modal fade" id="jobModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="jobModalTitle"></h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="jobForm" enctype="multipart/form-data">
                <div class="modal-body">

                    <input type="hidden" name="job_id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Client</label>
                            <input class="form-control" name="client_name" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Project</label>
                            <input class="form-control" name="project_name" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Job Subject</label>
                            <input class="form-control" name="job_subject" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Brief</label>
                            <textarea class="form-control" name="brief" rows="4" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-control" name="due_date" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Received At</label>
                            <input type="datetime-local" class="form-control" name="received_at">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Received Format</label>
                            <input class="form-control" name="received_format">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Received Attachment (≤ 1MB)</label>
                            <input type="file" class="form-control" name="received_format_file">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Reference Link</label>
                            <input type="url"
                                class="form-control"
                                name="reference_link"
                                placeholder="https://example.com">
                        </div>

                    </div>

                    <div id="jobFeedback" class="mt-3"></div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" id="jobSubmitBtn"></button>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function getJobModal() {
        return new bootstrap.Modal(document.getElementById('jobModal'));
    }

    function openCreateJob() {
        const form = document.getElementById('jobForm');
        document.getElementById('jobFeedback').innerHTML = '';
        form.reset();
        form.querySelector('[name="job_id"]').value = '';

        document.getElementById('jobModalTitle').innerText = 'Create New Job';
        document.getElementById('jobSubmitBtn').innerText = 'Create Job';

        form.onsubmit = e => submitJob(e, 'create_job_api.php');
        getJobModal().show();
    }

    function openEditJob(job) {
        const form = document.getElementById('jobForm');
        document.getElementById('jobFeedback').innerHTML = '';
        form.reset();

        // ✅ Explicitly set job_id (CRITICAL)
        form.querySelector('[name="job_id"]').value = job.id;

        // ✅ Map allowed editable fields only
        const map = [
            'client_name',
            'project_name',
            'job_subject',
            'brief',
            'due_date',
            'received_format',
            'received_at',
            'reference_link'
        ];

        map.forEach(key => {
            const field = form.querySelector(`[name="${key}"]`);
            if (!field) return;

            if (key === 'received_at' && job[key]) {
                field.value = job[key].replace(' ', 'T').slice(0, 16);
            } else {
                field.value = job[key] ?? '';
            }
        });


        document.getElementById('jobModalTitle').innerText = 'Edit Job ' + job.job_code;
        document.getElementById('jobSubmitBtn').innerText = 'Save Changes';

        form.onsubmit = e => submitJob(e, 'update_job_api.php');
        getJobModal().show();
    }


    function submitJob(e, url) {
        e.preventDefault();

        const form = document.getElementById('jobForm');
        const feedback = document.getElementById('jobFeedback');
        const isEdit = !!form.querySelector('[name="job_id"]').value;

        feedback.innerHTML = 'Saving…';

        fetch(url, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(r => r.json())
            .then(r => {
                if (!r.success) {
                    feedback.innerHTML =
                        `<div class="alert alert-danger">${r.error || 'Failed'}</div>`;
                    showToast(r.error || 'Operation failed', 'danger');
                    return;
                }

                showToast(
                    isEdit ? 'Job updated successfully' : 'Job created successfully', 'warning');

                setTimeout(() => {
                    location.reload();
                }, 1200);
            })
            .catch(() => {
                feedback.innerHTML =
                    '<div class="alert alert-danger">Server error</div>';
                showToast('Server error', 'danger');
            });
    }
</script>

</script>
<script>
    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('appToast');
        const msgEl = document.getElementById('toastMessage');

        toastEl.className =
            'toast align-items-center text-bg-' + type + ' border-0';

        msgEl.innerText = message;

        const toast = new bootstrap.Toast(toastEl, {
            delay: 1800
        });

        toast.show();
    }
</script>

<script>
    function deleteJob(jobId) {
        if (!confirm('Delete this job request?')) return;

        const data = new FormData();
        data.append('job_id', jobId);

        fetch('delete_job.php', {
                method: 'POST',
                body: data
            })
            .then(r => r.json())
            .then(r => {
                if (!r.success) {
                    showToast('Delete failed', 'danger');
                    return;
                }

                showToast('Job deleted', 'danger');

                setTimeout(() => {
                    location.reload();
                }, 1200);
            })
            .catch(() => {
                showToast('Server error', 'danger');
            });
    }
</script>

<!-- ================= TOAST CONTAINER ================= -->
<div class="toast-container position-fixed bottom-0 end-0 p-3"
    style="z-index: 1100">

    <div id="appToast" class="toast align-items-center text-bg-success border-0"
        role="alert" aria-live="assertive" aria-atomic="true">

        <div class="d-flex">
            <div class="toast-body" id="toastMessage">
                Success
            </div>
            <button type="button"
                class="btn-close btn-close-white me-2 m-auto"
                data-bs-dismiss="toast"></button>
        </div>

    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {

        /**
         * Apply alternating background colors
         * ONLY to real job rows (not header, not collapse, not empty rows)
         */
        function applyRowStriping() {
            const rows = document.querySelectorAll(
                'table.job-table tbody tr.job-row'
            );

            rows.forEach((row, index) => {
                row.classList.remove('alt');
                if (index % 2 === 1) {
                    row.classList.add('alt');
                }
            });
        }

        // Initial run
        applyRowStriping();

        // Re-apply after any collapse toggle (Bootstrap animation)
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
            btn.addEventListener('click', () => {
                setTimeout(applyRowStriping, 350);
            });
        });

    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {

        const allDetails = document.querySelectorAll('.job-detail');

        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
            btn.addEventListener('click', () => {

                const targetId = btn.getAttribute('data-bs-target');
                const targetEl = document.querySelector(targetId);

                allDetails.forEach(detail => {
                    if (detail !== targetEl && detail.classList.contains('show')) {
                        bootstrap.Collapse.getOrCreateInstance(detail).hide();
                    }
                });

            });
        });

    });
</script>


</body>

</html>