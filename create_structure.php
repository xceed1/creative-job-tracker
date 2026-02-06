<?php

$base = __DIR__;

$folders = [
    'config',
    'auth',
    'helpers',
    'assets/css',
    'assets/js',
    'assets/uploads',

    'admin',
    'traffic',
    'designer',
    'user'
];

$files = [
    'index.php',
    'config/db.php',

    'auth/login.php',
    'auth/logout.php',
    'auth/guard.php',

    'helpers/job_code.php',

    'admin/dashboard.php',
    'admin/users.php',
    'admin/departments.php',
    'admin/settings.php',

    'traffic/dashboard.php',
    'traffic/job_view.php',
    'traffic/dispatch.php',
    'traffic/decision.php',

    'designer/dashboard.php',
    'designer/update_status.php',

    'user/dashboard.php',
    'user/create_job.php'
];

foreach ($folders as $folder) {
    $path = $base . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
        echo "Created folder: $folder<br>";
    }
}

foreach ($files as $file) {
    $path = $base . DIRECTORY_SEPARATOR . $file;
    if (!file_exists($path)) {
        file_put_contents($path, "<?php\n// $file\n");
        echo "Created file: $file<br>";
    }
}

echo "<br><strong>✅ Project structure created successfully.</strong>";
