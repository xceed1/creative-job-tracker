<?php
session_start();
$sent = isset($_GET['sent']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <div class="card shadow-sm" style="max-width:420px;width:100%;">
        <div class="card-body p-4">
            <h5 class="text-center mb-3">Forgot Password</h5>

            <?php if ($sent): ?>
                <div class="alert alert-success small">
                    If the email exists, a reset link has been sent.
                </div>
            <?php endif; ?>

            <form method="post" action="forgot_password_submit.php">
                <div class="mb-3">
                    <label class="form-label small">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="d-grid">
                    <button class="btn btn-primary">Send Reset Link</button>
                </div>
            </form>

            <div class="text-center mt-3">
                <a href="login_form.php" class="small">Back to login</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
