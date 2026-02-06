<?php
// helpers/breadcrumbs.php

declare(strict_types=1);

function buildBreadcrumbs(string $role, string $pageTitle): array
{
    $home = match ($role) {
        'ADMIN'    => ['label' => 'Admin',    'url' => '/creative-job-tracker/admin/dashboard.php'],
        'TRAFFIC'  => ['label' => 'Traffic',  'url' => '/creative-job-tracker/traffic/dashboard.php'],
        'DESIGNER' => ['label' => 'Designer', 'url' => '/creative-job-tracker/designer/dashboard.php'],
        'USER'     => ['label' => 'My Requests','url' => '/creative-job-tracker/user/dashboard.php'],
        default    => ['label' => 'Home',     'url' => '/creative-job-tracker/index.php'],
    };

    return [
        $home,
        ['label' => $pageTitle, 'url' => null],
    ];
}
