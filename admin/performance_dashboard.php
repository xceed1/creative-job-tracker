<?php
require_once "../auth/guard.php";
requireRole(['EXECUTIVE']);
require_once "../config/db.php";

/*
|--------------------------------------------------------------------------
| DATE FILTER
|--------------------------------------------------------------------------
*/
$range = $_GET['range'] ?? '30';

$endDate = date('Y-m-d');
$startDate = match ($range) {
    '7'  => date('Y-m-d', strtotime('-7 days')),
    '30' => date('Y-m-d', strtotime('-30 days')),
    '90' => date('Y-m-d', strtotime('-90 days')),
    'custom' => $_GET['start'] ?? date('Y-m-d', strtotime('-30 days')),
    default => date('Y-m-d', strtotime('-30 days'))
};

if ($range === 'custom') {
    $endDate = $_GET['end'] ?? date('Y-m-d');
}

/*
|--------------------------------------------------------------------------
| GLOBAL KPIs
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM job_orders
    WHERE created_at BETWEEN :start AND :end
");
$stmt->execute(['start' => $startDate, 'end' => $endDate]);
$totalJobs = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM job_orders jo
    JOIN job_status js ON jo.status_id = js.id
    WHERE js.status_code = 'RELEASED'
      AND jo.created_at BETWEEN :start AND :end
");
$stmt->execute(['start' => $startDate, 'end' => $endDate]);
$completedJobs = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT AVG(TIMESTAMPDIFF(HOUR, jo.created_at, jo.updated_at))
    FROM job_orders jo
    JOIN job_status js ON jo.status_id = js.id
    WHERE js.status_code = 'RELEASED'
      AND jo.created_at BETWEEN :start AND :end
");
$stmt->execute(['start' => $startDate, 'end' => $endDate]);
$avgTurnaround = $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| DESIGNER PERFORMANCE
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        u.id AS designer_id,
        u.full_name,
        COUNT(ja.job_id) AS total_jobs,
        SUM(js.status_code = 'RELEASED') AS completed_jobs,
        AVG(TIMESTAMPDIFF(HOUR, ja.assigned_at, jo.updated_at)) AS avg_hours
    FROM job_assignments ja
    JOIN users u ON ja.designer_id = u.id
    JOIN job_orders jo ON ja.job_id = jo.id
    JOIN job_status js ON jo.status_id = js.id
    WHERE jo.created_at BETWEEN :start AND :end
    GROUP BY u.id
    ORDER BY completed_jobs DESC
");
$stmt->execute(['start' => $startDate, 'end' => $endDate]);
$designerStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| DEPARTMENT PERFORMANCE
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        d.dept_name,
        COUNT(jo.id) AS total_jobs,
        SUM(js.status_code = 'RELEASED') AS completed_jobs,
        AVG(
            CASE 
                WHEN js.status_code = 'RELEASED'
                THEN TIMESTAMPDIFF(HOUR, jo.created_at, jo.updated_at)
            END
        ) AS avg_turnaround
    FROM job_orders jo
    JOIN job_status js ON jo.status_id = js.id
    LEFT JOIN departments d ON jo.requesting_department_id = d.id
    WHERE jo.created_at BETWEEN :start AND :end
    GROUP BY d.id
    ORDER BY total_jobs DESC
");
$stmt->execute(['start' => $startDate, 'end' => $endDate]);
$departmentStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| DEPARTMENT RANKING
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        d.dept_name,
        COUNT(jo.id) AS total_jobs,
        SUM(js.status_code = 'RELEASED') AS completed_jobs,
        ROUND(
            (SUM(js.status_code = 'RELEASED') / COUNT(jo.id)) * 100,
            1
        ) AS completion_rate,
        ROUND(
            AVG(
                CASE 
                    WHEN js.status_code = 'RELEASED'
                    THEN TIMESTAMPDIFF(HOUR, jo.created_at, jo.updated_at)
                END
            ),
            1
        ) AS avg_turnaround
    FROM job_orders jo
    JOIN job_status js ON jo.status_id = js.id
    LEFT JOIN departments d ON jo.requesting_department_id = d.id
    WHERE jo.created_at BETWEEN :start AND :end
    GROUP BY d.id
    HAVING total_jobs > 0
    ORDER BY completion_rate DESC, avg_turnaround ASC
");
$stmt->execute(['start' => $startDate, 'end' => $endDate]);
$departmentRanking = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| TRAFFIC ACTIVITY
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        u.full_name,
        COUNT(al.id) AS actions_count
    FROM job_activity_log al
    JOIN users u ON al.user_id = u.id
    JOIN job_orders jo ON al.job_id = jo.id
    WHERE al.action_code LIKE 'TRAFFIC_%'
      AND jo.created_at BETWEEN :start AND :end
    GROUP BY u.id
    ORDER BY actions_count DESC
");
$stmt->execute(['start' => $startDate, 'end' => $endDate]);
$trafficStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| MONTHLY JOB VOLUME
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS month,
        COUNT(*) AS total
    FROM job_orders
    WHERE created_at BETWEEN :start AND :end
    GROUP BY month
    ORDER BY month
");
$stmt->execute(['start' => $startDate, 'end' => $endDate]);
$monthlyJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Executive Performance Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-light">

    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>📊 Executive Performance Dashboard</h3>
            <a href="dashboard.php" class="btn btn-secondary btn-sm">⬅ Back</a>
        </div>
        <!-- DATE FILTER -->
        <form class="row g-2 mb-4 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date Range</label>
                <select name="range" class="form-select" onchange="this.form.submit()">
                    <option value="7" <?= $range === '7'  ? 'selected' : '' ?>>Last 7 days</option>
                    <option value="30" <?= $range === '30' ? 'selected' : '' ?>>Last 30 days</option>
                    <option value="90" <?= $range === '90' ? 'selected' : '' ?>>Last 90 days</option>
                    <option value="custom" <?= $range === 'custom' ? 'selected' : '' ?>>Custom</option>
                </select>
            </div>

            <?php if ($range === 'custom'): ?>
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date"
                        name="start"
                        class="form-control"
                        value="<?= htmlspecialchars($startDate) ?>"
                        required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date"
                        name="end"
                        class="form-control"
                        value="<?= htmlspecialchars($endDate) ?>"
                        required>
                </div>

                <div class="col-md-2">
                    <button class="btn btn-primary w-100">Apply</button>
                </div>
            <?php endif; ?>
        </form>

        <!-- KPIs -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm text-center">
                    <div class="card-body">
                        <h6>Total Jobs</h6>
                        <h3><?= (int)$totalJobs ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm text-center">
                    <div class="card-body">
                        <h6>Completed Jobs</h6>
                        <h3><?= (int)$completedJobs ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm text-center">
                    <div class="card-body">
                        <h6>Avg Turnaround (hrs)</h6>
                        <h3><?= $avgTurnaround ? round($avgTurnaround, 1) : '—' ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6>Monthly Job Volume</h6>
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6>Traffic Activity</h6>
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Charts -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6>Jobs by Department</h6>
                        <canvas id="deptJobsChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6>Avg Turnaround by Department</h6>
                        <canvas id="deptTurnaroundChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Ranking -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h6>🏆 Department Performance Ranking</h6>
                <table class="table table-bordered table-hover align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Department</th>
                            <th>Total Jobs</th>
                            <th>Completed</th>
                            <th>Completion %</th>
                            <th>Avg Turnaround (hrs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departmentRanking as $i => $d): ?>
                            <tr class="<?= $i === 0 ? 'table-success' : '' ?>">
                                <td><strong><?= $i + 1 ?></strong></td>
                                <td><?= htmlspecialchars($d['dept_name']) ?></td>
                                <td><?= (int)$d['total_jobs'] ?></td>
                                <td><?= (int)$d['completed_jobs'] ?></td>
                                <td><?= $d['completion_rate'] ?>%</td>
                                <td><?= $d['avg_turnaround'] ?? '—' ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- DESIGNER RANKING -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h6>🏅 Designer Performance Ranking</h6>

                <table class="table table-bordered table-hover align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Designer</th>
                            <th>Total Jobs</th>
                            <th>Completed</th>
                            <th>Avg Turnaround (hrs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($designerStats as $i => $d): ?>
                            <tr class="<?= $i === 0 ? 'table-success' : '' ?>">
                                <td><strong><?= $i + 1 ?></strong></td>
                                <td><?= htmlspecialchars($d['full_name']) ?></td>
                                <td><?= (int)$d['total_jobs'] ?></td>
                                <td><?= (int)$d['completed_jobs'] ?></td>
                                <td><?= $d['avg_hours'] ? round($d['avg_hours'], 1) : '—' ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>


    </div>

    <script>
        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($monthlyJobs, 'month')) ?>,
                datasets: [{
                    label: 'Jobs',
                    data: <?= json_encode(array_column($monthlyJobs, 'total')) ?>
                }]
            }
        });

        new Chart(document.getElementById('trafficChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($trafficStats, 'full_name')) ?>,
                datasets: [{
                    label: 'Actions',
                    data: <?= json_encode(array_column($trafficStats, 'actions_count')) ?>
                }]
            }
        });

        new Chart(document.getElementById('deptJobsChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($departmentStats, 'dept_name')) ?>,
                datasets: [{
                    label: 'Jobs',
                    data: <?= json_encode(array_column($departmentStats, 'total_jobs')) ?>
                }]
            }
        });

        new Chart(document.getElementById('deptTurnaroundChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($departmentStats, 'dept_name')) ?>,
                datasets: [{
                    label: 'Avg Hours',
                    data: <?= json_encode(array_map(fn($d) => round($d['avg_turnaround'], 1), $departmentStats)) ?>
                }]
            }
        });
    </script>

</body>

</html>