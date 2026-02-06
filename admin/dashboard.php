<?php
require_once "../auth/guard.php";
requireRole(['ADMIN']);
require_once "../config/db.php";

// KPIs
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalJobs  = $pdo->query("SELECT COUNT(*) FROM job_orders")->fetchColumn();
$openJobs   = $pdo->query("
    SELECT COUNT(*) FROM job_orders jo
    JOIN job_status js ON jo.status_id = js.id
    WHERE js.is_final = 0
")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
<div class="container py-4">
    <h2 class="mb-4">Admin Dashboard</h2>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Total Users</h6>
                    <h3><?= $totalUsers ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Total Job Orders</h6>
                    <h3><?= $totalJobs ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Open Jobs</h6>
                    <h3><?= $openJobs ?></h3>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <div class="list-group">
        <a href="users.php" class="list-group-item list-group-item-action">👤 Manage Users</a>
        <a href="departments.php" class="list-group-item list-group-item-action">🏢 Manage Departments</a>
        <a href="settings.php" class="list-group-item list-group-item-action">⚙ System Settings</a>
    </div>
</div>

</body>
</html>
