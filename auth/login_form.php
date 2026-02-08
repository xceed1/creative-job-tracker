<?php
// Public login form
// NO guard
// NO redirects here

session_start();

$error = $_GET['error'] ?? null;

$errorMessage = match ($error) {
    '1' => 'Invalid email or password.',
    'department_required' => 'Your account is missing a department assignment. Please contact administration.',
    'system' => 'The system is temporarily unavailable. Please try again later.',
    default => null
};
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sign In — Service Request Platform</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        }

        .login-container {
            min-height: 100vh;
        }

        .login-card {
            max-width: 420px;
            width: 100%;
        }

        .brand {
            font-weight: 700;
            letter-spacing: -0.4px;
        }

        .brand-sub {
            font-size: 0.9rem;
            color: #6c757d;
        }

        footer {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <!-- ================= NAVBAR ================= -->
    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">

            <div class="d-flex align-items-center w-100 px-4">
                <a href="/creative-job-tracker/index.php"
                    class="navbar-brand d-flex align-items-center gap-2 fw-semibold">

                    <img src="/creative-job-tracker/assets/pureminds.png"
                        height="32"
                        alt="Pureminds Logo">

                    <!-- <span class="d-none d-md-inline">
                    Service Request Platform
                </span> -->
                </a>
            </div>

        </div>
    </nav>




    <div class="container login-container d-flex align-items-center justify-content-center">

        <div class="login-card">

            <!-- BRAND -->
            <div class="text-center mb-4">
                <div class="brand h4 mb-1">Service Request Platform</div>
                <div class="brand-sub">
                    Secure access for authorized users
                </div>
            </div>

            <!-- CARD -->
            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h5 class="fw-semibold mb-3 text-center">
                        Sign In
                    </h5>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger small">
                            <?= htmlspecialchars($errorMessage) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="login.php" autocomplete="off">

                        <div class="mb-3">
                            <label class="form-label small text-muted">
                                Email Address
                            </label>
                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                required
                                autofocus>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">
                                Password
                            </label>
                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                required>
                        </div>

                        <div class="d-grid mt-4">
                            <button class="btn btn-primary">
                                Sign In
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Forgot your password? Please contact system administrator.
                            </small>
                        </div>


                    </form>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="text-center mt-4">
                <a href="/creative-job-tracker/index.php"
                    class="text-decoration-none small">
                    ← Back to home
                </a>
            </div>

            <!-- ================= FOOTER ================= -->
            <footer class="py-4">
                <div class="container text-center">
                    <div class="mb-1">
                        PUREMINDS <?= date('Y') ?> Service Request Management Platform
                    </div>
                    <div class="text-muted">
                        Internal enterprise system • All rights reserved
                    </div>
                </div>
            </footer>

        </div>

    </div>

</body>

</html>