<?php
require_once "../auth/guard.php";
require_once "../config/db.php";

$userId = $_SESSION['user']['id'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $new     = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new === '' || $new !== $confirm || strlen($new) < 8) {
        $error = 'Passwords must match and be at least 8 characters long.';
    } else {

        $hash = password_hash($new, PASSWORD_DEFAULT);

        $pdo->prepare("
            UPDATE users
            SET
                password = ?,
                force_password_change = 0
            WHERE id = ?
        ")->execute([$hash, $userId]);

        // ✅ Sync session
        $_SESSION['user']['force_password_change'] = 0;

        switch ($_SESSION['user']['role']) {

            case 'ADMIN':
                header("Location: /creative-job-tracker/admin/dashboard.php");
                break;

            case 'TRAFFIC':
                header("Location: /creative-job-tracker/traffic/dashboard.php");
                break;

            case 'DESIGNER':
                header("Location: /creative-job-tracker/designer/dashboard.php");
                break;

            case 'EXECUTIVE':
                header("Location: /creative-job-tracker/admin/performance_dashboard.php");
                break;

            case 'USER':
            default:
                header("Location: /creative-job-tracker/user/dashboard.php");
                break;
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Change Password — Service Request Platform</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        }

        .card {
            max-width: 420px;
            margin: auto;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <span class="navbar-brand fw-semibold">Service Request Platform</span>
        </div>
    </nav>

    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card shadow-sm">
            <div class="card-body p-4">

                <h5 class="fw-semibold mb-3 text-center">
                    Change Your Password
                </h5>

                <p class="text-muted small text-center mb-4">
                    For security reasons, you must change your password before continuing.
                </p>

                <?php if ($error): ?>
                    <div class="alert alert-danger small">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="off">

                    <div class="mb-3">
                        <label class="form-label small text-muted">
                            New Password
                        </label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">
                            Confirm Password
                        </label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <div class="d-grid mt-4">
                        <button class="btn btn-primary">
                            Update Password
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

</body>

</html>