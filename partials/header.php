<?php
$user = $_SESSION['user'];
?>

<header class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container-fluid px-4 d-flex justify-content-between">

        <div class="d-flex align-items-center gap-2">
            <img src="/creative-job-tracker/assets/logo.svg" height="28" alt="Logo">
            <span class="navbar-brand fw-semibold mb-0">
                Service Request Platform
            </span>
        </div>

        <div class="d-flex align-items-center gap-3">
            <span class="text-light small">
                Hello, <?= htmlspecialchars($user['full_name']) ?>
            </span>
            <a href="/creative-job-tracker/auth/logout.php"
               class="btn btn-sm btn-outline-light">
                Logout
            </a>
        </div>

    </div>
</header>
