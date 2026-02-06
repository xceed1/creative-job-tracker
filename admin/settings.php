<?php
require_once "../auth/guard.php";
requireRole(['ADMIN']);
require_once "../config/db.php";

$settings = $pdo->query("
    SELECT setting_key, setting_value, setting_type
    FROM system_settings
    ORDER BY setting_key
")->fetchAll();
?>
<!DOCTYPE html>
<html>

<head>
    <title>System Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <span class="navbar-brand fw-semibold">Creative Job Tracker</span>
            <div class="d-flex gap-3 align-items-center">
                <span class="text-light small">Traffic Manager</span>
                <a href="../auth/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container py-4">
        <h3>System Settings</h3>

        <form onsubmit="return false;">
            <?php foreach ($settings as $s): ?>
                <div class="mb-3">
                    <label class="form-label">
                        <?= htmlspecialchars($s['setting_key']) ?>
                    </label>

                    <input class="form-control"
                        name="settings[<?= htmlspecialchars($s['setting_key']) ?>]"
                        data-type="<?= htmlspecialchars($s['setting_type']) ?>"
                        value="<?= htmlspecialchars($s['setting_value']) ?>">
                </div>
            <?php endforeach ?>

            <button class="btn btn-success" onclick="saveSettings()">Save Settings</button>
            <a href="dashboard.php" class="btn btn-secondary">Back</a>

            <div id="settings-feedback" class="mt-3"></div>
        </form>
    </div>

    <script>
        function saveSettings() {

            const inputs = document.querySelectorAll('[name^="settings"]');
            const data = {};

            inputs.forEach(input => {
                data[input.name] = input.value;
            });

            const feedback = document.getElementById('settings-feedback');
            feedback.innerHTML = 'Saving...';

            fetch('update_settings_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams(data)
                })
                .then(r => r.json())
                .then(r => {
                    if (!r.success) {
                        feedback.innerHTML =
                            '<div class="alert alert-danger">' + r.error + '</div>';
                        return;
                    }

                    feedback.innerHTML =
                        '<div class="alert alert-success">Settings saved successfully</div>';
                })
                .catch(() => {
                    feedback.innerHTML =
                        '<div class="alert alert-danger">Server error</div>';
                });
        }
    </script>

</body>

</html>