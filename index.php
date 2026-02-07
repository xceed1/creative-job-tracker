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
    <title>Service Request Management Platform</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        }

        .hero {
            padding: 5rem 1rem;
        }

        .hero-title {
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .hero-subtitle {
            max-width: 720px;
            margin: 0 auto;
        }

        .feature-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #0d6efd;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        footer {
            font-size: 0.85rem;
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

                    <img src="/creative-job-tracker/assets/Untitled-1.jpg"
                        height="32"
                        alt="Pureminds logo">

                    <span class="d-none d-md-inline">
                        Service Request Platform
                    </span>
                </a>
            </div>

        </div>
    </nav>
    <!-- ================= HERO ================= -->
    <section class="hero text-center">
        <div class="container">

            <h1 class="hero-title display-5 mb-3">
                Centralized Service Request Management
            </h1>

            <p class="hero-subtitle lead text-muted mb-4">
                A unified platform to submit, track, approve, and deliver service requests
                across departments — from creative and content to operations, IT, and shared services.
            </p>

            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="/creative-job-tracker/auth/login_form.php"
                    class="btn btn-primary btn-lg px-4">
                    Access Platform
                </a>

                <button class="btn btn-outline-secondary btn-lg px-4" disabled>
                    Request Access
                </button>
            </div>

        </div>
    </section>

    <!-- ================= FEATURES ================= -->
    <section class="py-5 bg-light">
        <div class="container">

            <div class="row text-center mb-4">
                <div class="col">
                    <h3 class="fw-semibold">Designed for Operational Excellence</h3>
                    <p class="text-muted mt-2">
                        Built to enforce process, accountability, and visibility — not shortcuts.
                    </p>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3">01</div>
                            <h6 class="fw-semibold">Structured Workflow</h6>
                            <p class="text-muted small mb-0">
                                Requests follow a strict, auditable lifecycle from submission
                                to approval and final release.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3">02</div>
                            <h6 class="fw-semibold">Role-Based Control</h6>
                            <p class="text-muted small mb-0">
                                Clear separation of responsibilities between requesters,
                                executors, reviewers, and administrators.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3">03</div>
                            <h6 class="fw-semibold">Real-Time Visibility</h6>
                            <p class="text-muted small mb-0">
                                Track progress, bottlenecks, and delivery status with
                                complete transparency across teams.
                            </p>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>

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

</body>

</html>