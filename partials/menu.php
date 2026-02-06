<?php
// partials/menu.php

return [

    'COMMON' => [
        [
            'label' => 'Dashboard',
            'icon'  => 'bi-speedometer2',
            'url'   => '/creative-job-tracker/'
        ],
    ],

    'USER' => [
        [
            'label' => 'My Requests',
            'icon'  => 'bi-folder',
            'url'   => '/creative-job-tracker/user/dashboard.php'
        ],
        [
            'label' => 'New Request',
            'icon'  => 'bi-plus-circle',
            'url'   => '/creative-job-tracker/user/dashboard.php#new'
        ],
    ],

    'DESIGNER' => [
        [
            'label' => 'My Assignments',
            'icon'  => 'bi-brush',
            'url'   => '/creative-job-tracker/designer/dashboard.php'
        ],
    ],

    'TRAFFIC' => [
        [
            'label' => 'Traffic Dashboard',
            'icon'  => 'bi-kanban',
            'url'   => '/creative-job-tracker/traffic/dashboard.php'
        ],
    ],

    'ADMIN' => [
        [
            'label' => 'Admin Dashboard',
            'icon'  => 'bi-shield-lock',
            'url'   => '/creative-job-tracker/admin/dashboard.php'
        ],
        [
            'label' => 'System Settings',
            'icon'  => 'bi-gear',
            'url'   => '/creative-job-tracker/admin/settings.php'
        ],
    ],

    'EXECUTIVE' => [
        [
            'label' => 'Performance',
            'icon'  => 'bi-graph-up',
            'url'   => '/creative-job-tracker/admin/performance_dashboard.php'
        ],
    ],
];
