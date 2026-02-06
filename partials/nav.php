<?php
// partials/nav.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$role = $_SESSION['user']['role'] ?? null;
$current = $_SERVER['SCRIPT_NAME'];

function isActive(string $path): string
{
    return str_contains($_SERVER['SCRIPT_NAME'], $path)
        ? 'active fw-semibold'
        : '';
}
?>

<style>
/* ===== SIDEBAR ===== */
.sidebar {
    width: 240px;
    min-height: calc(100vh - 56px);
    background: #212529;
}

.sidebar a {
    color: #adb5bd;
    text-decoration: none;
}

.sidebar a:hover,
.sidebar a.active {
    background: #343a40;
    color: #fff;
}

.sidebar .nav-link {
    padding: 0.65rem 1rem;
    border-radius: 0.375rem;
}
</style>

<div class="d-flex">

    <!-- SIDEBAR -->
    <aside class="sidebar p-3">

        <div class="mb-3 text-uppercase text-muted small">
            Navigation
        </div>

        <ul class="nav nav-pills flex-column gap-1">

            <?php if ($role === 'USER'): ?>
                <li>
                    <a class="nav-link <?= isActive('/user/dashboard.php') ?>"
                       href="/creative-job-tracker/user/dashboard.php">
                        📝 My Requests
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($role === 'TRAFFIC'): ?>
                <li>
                    <a class="nav-link <?= isActive('/traffic/dashboard.php') ?>"
                       href="/creative-job-tracker/traffic/dashboard.php">
                        🚦 Traffic Dashboard
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($role === 'DESIGNER'): ?>
                <li>
                    <a class="nav-link <?= isActive('/designer/dashboard.php') ?>"
                       href="/creative-job-tracker/designer/dashboard.php">
                        🎨 My Tasks
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($role, ['ADMIN','EXECUTIVE'], true)): ?>
                <li>
                    <a class="nav-link <?= isActive('/admin/dashboard.php') ?>"
                       href="/creative-job-tracker/admin/dashboard.php">
                        🛠 Admin Dashboard
                    </a>
                </li>

                <li>
                    <a class="nav-link <?= isActive('/admin/performance_dashboard.php') ?>"
                       href="/creative-job-tracker/admin/performance_dashboard.php">
                        📊 Performance
                    </a>
                </li>
            <?php endif; ?>

        </ul>

    </aside>

    <!-- PAGE CONTENT WRAPPER -->
    <main class="flex-grow-1">
