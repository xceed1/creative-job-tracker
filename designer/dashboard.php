<?php
require_once "../auth/guard.php";
requireRole(['DESIGNER']);
require_once "../config/db.php";

$designerId = $_SESSION['user']['id'];

/* ============================================================
| LOAD ASSIGNMENTS FOR THIS DESIGNER
============================================================ */
$jobsStmt = $pdo->prepare("
    SELECT 
        ja.id AS assignment_id,
        ja.assignment_status,
        ja.traffic_note,
        ja.estimated_hours,
        ja.assigned_due_at,
        ja.completion_link,

        jo.id AS job_id,
        jo.job_code,
        jo.project_name,
        jo.job_subject,
        jo.brief,
        jo.reference_link,

        js.status_code,
        js.status_name
    FROM job_assignments ja
    JOIN job_orders jo ON ja.job_id = jo.id
    JOIN job_status js ON jo.status_id = js.id
    WHERE ja.designer_id = ?
    ORDER BY ja.assigned_due_at ASC
");
$jobsStmt->execute([$designerId]);
$jobs = $jobsStmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
| STATUS COLOR (JOB STATUS)
============================================================ */
function statusColor(string $code): string
{
    return match ($code) {
        'DISPATCHED'  => 'primary',
        'IN_PROGRESS' => 'warning',
        'COMPLETED'   => 'info',
        'RELEASED'    => 'success',
        default       => 'secondary'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Designer Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Designer Dashboard</h3>
        <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>

<?php if (!$jobs): ?>
    <div class="alert alert-info">No tasks assigned yet.</div>
<?php else: ?>

<div class="card shadow-sm">
<div class="card-body p-0">
<table class="table table-bordered table-hover mb-0 align-middle">
<thead class="table-dark">
<tr>
    <th>Job</th>
    <th>Details</th>
    <th>Hours</th>
    <th>Due</th>
    <th>Status</th>
    <th width="260">Action</th>
</tr>
</thead>
<tbody>

<?php foreach ($jobs as $job): ?>
<tr>
<td><strong><?= htmlspecialchars($job['job_code']) ?></strong></td>

<td>
<button class="btn btn-sm btn-outline-secondary"
        data-bs-toggle="collapse"
        data-bs-target="#details<?= (int)$job['assignment_id'] ?>">
View Details
</button>
</td>

<td><?= $job['estimated_hours'] !== null ? number_format((float)$job['estimated_hours'],2).' hrs' : '—' ?></td>
<td><?= htmlspecialchars($job['assigned_due_at'] ?? '—') ?></td>

<td>
<span class="badge bg-<?= statusColor($job['status_code']) ?>">
<?= htmlspecialchars($job['status_name']) ?>
</span>
</td>

<td>
<?php if ($job['status_code'] === 'RELEASED'): ?>

    <span class="badge bg-success w-100 text-center">Job Released</span>

<?php elseif (in_array($job['assignment_status'], ['ASSIGNED','REJECTED'], true)): ?>

    <button class="btn btn-sm btn-primary"
        onclick="startTask(<?= (int)$job['assignment_id'] ?>)">
        Start Task
    </button>

<?php elseif ($job['assignment_status'] === 'IN_PROGRESS'): ?>

    <button class="btn btn-sm btn-success w-100"
        data-bs-toggle="collapse"
        data-bs-target="#complete<?= (int)$job['assignment_id'] ?>">
        Complete Task
    </button>

<?php elseif ($job['assignment_status'] === 'COMPLETED'): ?>

    <a href="<?= htmlspecialchars($job['completion_link']) ?>"
       target="_blank"
       class="btn btn-sm btn-outline-success w-100">
       View Submitted Work
    </a>

    <div class="small mt-1 <?= $job['status_code']==='RELEASED'?'text-success':'text-muted' ?>">
        <?= $job['status_code']==='RELEASED'
            ? '✔ Job released to requester'
            : 'Waiting for traffic release' ?>
    </div>

<?php endif; ?>
</td>
</tr>

<tr class="collapse bg-light" id="details<?= (int)$job['assignment_id'] ?>">
<td colspan="6">
<strong>Project:</strong> <?= htmlspecialchars($job['project_name']) ?><br>
<strong>Subject:</strong> <?= htmlspecialchars($job['job_subject']) ?><br><br>
<strong>Brief:</strong><br><?= nl2br(htmlspecialchars($job['brief'])) ?><br><br>

<?php if ($job['reference_link']): ?>
<strong>Reference:</strong><br>
<a href="<?= htmlspecialchars($job['reference_link']) ?>" target="_blank">
<?= htmlspecialchars($job['reference_link']) ?>
</a><br><br>
<?php endif; ?>

<strong>Traffic Note:</strong><br>
<?= nl2br(htmlspecialchars($job['traffic_note'])) ?>
</td>
</tr>

<?php if ($job['assignment_status']==='IN_PROGRESS'): ?>
<tr class="collapse bg-light" id="complete<?= (int)$job['assignment_id'] ?>">
<td colspan="6">
<input type="url" class="form-control mb-2"
       placeholder="https://"
       id="link<?= (int)$job['assignment_id'] ?>">
<button class="btn btn-sm btn-success w-100"
    onclick="completeTask(<?= (int)$job['assignment_id'] ?>)">
    Complete Task
</button>
<div id="designer-feedback<?= (int)$job['assignment_id'] ?>" class="small mt-1"></div>
</td>
</tr>
<?php endif; ?>

<?php endforeach; ?>

</tbody>
</table>
</div>
</div>

<?php endif; ?>

</div>

<script>
function startTask(id){
fetch('update_status_api.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:new URLSearchParams({assignment_id:id,action:'start'})
})
.then(r=>r.json())
.then(r=>{
if(!r.success){alert(r.error);return;}
location.reload();
})
.catch(()=>alert('Server error'));
}

function completeTask(id){
const link=document.getElementById('link'+id).value;
const fb=document.getElementById('designer-feedback'+id);
if(!link){fb.innerHTML='<span class="text-danger">Link required</span>';return;}

fetch('update_status_api.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:new URLSearchParams({
assignment_id:id,
action:'complete',
completion_link:link
})
})
.then(r=>r.json())
.then(r=>{
if(!r.success){fb.innerHTML='<span class="text-danger">'+r.error+'</span>';return;}
location.reload();
})
.catch(()=>fb.innerHTML='<span class="text-danger">Server error</span>');
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>