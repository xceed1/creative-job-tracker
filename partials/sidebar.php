<?php
$currentPath = $_SERVER['REQUEST_URI'];
$role = $_SESSION['user']['role'];

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/nav_counters.php';

$counters = getNavCounters($pdo, $role);

$menu = [

    'TRAFFIC' => [
        [
            'label' => 'Dashboard',
            'icon'  => 'bi-speedometer2',
            'url'   => '/creative-job-tracker/traffic/dashboard.php',
        ],
        [
            'label' => 'New Jobs',
            'icon'  => 'bi-inbox',
            'url'   => '/creative-job-tracker/traffic/dashboard.php',
            'badge' => $counters['new_jobs'] ?? 0,
            'badge_class' => 'bg-primary',
        ],
        [
            'label' => 'Action Required',
            'icon'  => 'bi-exclamation-circle',
            'url'   => '/creative-job-tracker/traffic/dashboard.php',
            'badge' => $counters['action_required'] ?? 0,
            'badge_class' => 'bg-danger',
        ],
    ],

    'ADMIN' => [
        [
            'label' => 'Dashboard',
            'icon'  => 'bi-speedometer2',
            'url'   => '/creative-job-tracker/admin/dashboard.php',
        ],
        [
            'label' => 'Users',
            'icon'  => 'bi-people',
            'url'   => '/creative-job-tracker/admin/users.php',
        ],
        [
            'label' => 'Settings',
            'icon'  => 'bi-gear',
            'url'   => '/creative-job-tracker/admin/settings.php',
        ],
    ],

    'EXECUTIVE' => [
        [
            'label' => 'Performance',
            'icon'  => 'bi-graph-up',
            'url'   => '/creative-job-tracker/admin/performance_dashboard.php',
        ],
    ],
];

$items = $menu[$role] ?? [];
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<div class="d-flex" style="min-height:calc(100vh - 56px);">

    <!-- SIDEBAR -->
    <aside class="border-end bg-white d-flex flex-column"
           style="width:240px;">

        <!-- ROLE -->
        <div class="p-3 border-bottom fw-semibold text-uppercase small text-muted">
            <?= htmlspecialchars($role) ?>
        </div>

        <!-- NAV -->
        <ul class="nav flex-column p-2 flex-grow-1">
            <?php foreach ($items as $item): ?>
                <?php
                $active = str_starts_with($currentPath, $item['url'])
                    ? 'active fw-semibold'
                    : '';
                ?>
                <li class="nav-item">
                    <a class="nav-link d-flex justify-content-between align-items-center <?= $active ?>"
                       href="<?= $item['url'] ?>">

                        <span class="d-flex align-items-center gap-2">
                            <i class="bi <?= $item['icon'] ?>"></i>
                            <?= $item['label'] ?>
                        </span>

                        <?php if (!empty($item['badge'])): ?>
                            <span class="badge <?= $item['badge_class'] ?? 'bg-secondary' ?>">
                                <?= (int)$item['badge'] ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- SIDEBAR FOOTER -->
        <div class="border-top p-3 text-center small text-muted">
            <?= date('Y') ?> PUREMINDS<br>
            Service Request Platform
        </div>

    </aside>

    <!-- PAGE CONTENT -->
    <main class="flex-grow-1">
