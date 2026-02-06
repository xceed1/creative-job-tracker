<?php
declare(strict_types=1);

/* ============================================================
| SESSION
============================================================ */
session_start();

/* ============================================================
| AUTO REDIRECT IF LOGGED IN
============================================================ */
if (isset($_SESSION['user'])) {
    switch ($_SESSION['user']['role']) {
        case 'ADMIN':
            header("Location: ../admin/dashboard.php");
            break;

        case 'TRAFFIC':
            header("Location: ../traffic/dashboard.php");
            break;

        case 'DESIGNER':
            header("Location: ../designer/dashboard.php");
            break;

        case 'EXECUTIVE':
            header("Location: ../admin/performance_dashboard.php");
            break;

        case 'USER':
        default:
            header("Location: ../user/dashboard.php");
            break;
    }
    exit;
}

/* ============================================================
| ERROR MAPPING (SAFE & USER-FRIENDLY)
============================================================ */
$error = $_GET['error'] ?? null;

$message = null;

switch ($error) {
    case '1':
        $message = 'Invalid email or password';
        break;

    case 'department_required':
        $message = 'Your account is not linked to a department';
        break;

    case 'system':
        $message = 'System temporarily unavailable. Please try again.';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <div class="card shadow-sm" style="width: 380px;">
        <div class="card-body">

            <h4 class="text-center mb-3">Login</h4>

            <?php if ($message): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/creative-job-tracker/auth/login.php">

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        required
                        autofocus
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Login
                </button>

            </form>

        </div>
    </div>
</div>

</body>
</html>