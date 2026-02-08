<?php
session_start();
require_once "../config/db.php";

$error   = $_GET['error'] ?? null;
$success = isset($_GET['success']);

$stmt = $pdo->query("
    SELECT id, dept_name
    FROM departments
    WHERE is_active = 1
    ORDER BY dept_name
");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Access — Service Request Platform</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%); }
        .form-container { min-height: 100vh; }
        .form-card { max-width: 480px; width: 100%; }
        .brand { font-weight: 700; letter-spacing: -0.4px; }
        .brand-sub { font-size: .9rem; color: #6c757d; }
        footer { font-size: .8rem; color: #6c757d; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container-fluid px-4">
        <a href="/creative-job-tracker/index.php" class="navbar-brand d-flex align-items-center gap-2 fw-semibold">
            <img src="/creative-job-tracker/assets/pureminds.png" height="32">
        </a>
    </div>
</nav>

<div class="container form-container d-flex align-items-center justify-content-center">
    <div class="form-card">

        <div class="text-center mb-4">
            <div class="brand h4 mb-1">Service Request Platform</div>
            <div class="brand-sub">Request access for internal use</div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">

                <h5 class="fw-semibold text-center mb-3">Request Access</h5>

                <?php if ($error): ?>
                    <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success small">
                        Your request has been submitted.<br>
                        An administrator will review it shortly.
                    </div>
                <?php endif; ?>

                <form method="post" action="request_access_submit.php">

                    <div class="mb-3">
                        <label class="form-label small text-muted">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Department</label>
                        <select name="department_id" class="form-select" required>
                            <option value="">Select department</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>">
                                    <?= htmlspecialchars($d['dept_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-grid mt-4">
                        <button class="btn btn-primary">Submit Request</button>
                    </div>

                </form>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="/creative-job-tracker/index.php" class="small text-decoration-none">
                ← Back to home
            </a>
        </div>

        <footer class="py-4 text-center">
            PUREMINDS <?= date('Y') ?> • Internal System
        </footer>
    </div>
</div>

</body>
</html>
