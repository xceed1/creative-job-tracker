<?php
// Public landing page
// NO session redirect
// NO guard

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Creative Job Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-md-8 text-center">

            <h1 class="mb-3">🎨 Creative Job Tracker</h1>
            <p class="lead text-muted">
                Manage creative requests, approvals, and production workflow in one place.
            </p>

            <hr class="my-4">

            <div class="d-flex justify-content-center gap-3">

                <a href="/creative-job-tracker/auth/login_form.php" class="btn btn-primary btn-lg">
                    Login
                </a>

                <a href="#" class="btn btn-outline-secondary btn-lg disabled">
                    Request Access
                </a>

            </div>

            <p class="text-muted mt-4 small">
                © <?= date('Y') ?> Creative Job Tracker
            </p>

        </div>
    </div>

</div>

</body>
</html>
