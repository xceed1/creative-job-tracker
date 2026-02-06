<?php
require_once "../auth/guard.php";
requireRole(['ADMIN']);
require_once "../config/db.php";

$departments = $pdo->query("
    SELECT id, dept_code, dept_name, is_active
    FROM departments
    ORDER BY dept_name
")->fetchAll();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Departments</title>
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
        <h3>Departments</h3>

        <button class="btn btn-primary mb-3"
            data-bs-toggle="modal"
            data-bs-target="#addDeptModal">
            Add Department
        </button>

        <table class="table table-bordered table-hover bg-white shadow-sm">
            <thead class="table-dark">
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th width="180">Actions</th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($departments as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['dept_code']) ?></td>
                        <td><?= htmlspecialchars($d['dept_name']) ?></td>
                        <td>
                            <span class="badge bg-<?= $d['is_active'] ? 'success' : 'secondary' ?>">
                                <?= $d['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#editDept<?= $d['id'] ?>">
                                Edit
                            </button>

                            <button class="btn btn-sm btn-<?= $d['is_active'] ? 'danger' : 'success' ?>"
                                onclick="toggleDepartment(<?= $d['id'] ?>)">
                                <?= $d['is_active'] ? 'Disable' : 'Enable' ?>
                            </button>
                        </td>
                    </tr>

                    <!-- EDIT MODAL -->
                    <div class="modal fade" id="editDept<?= $d['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">

                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Department</h5>
                                    <button class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">

                                    <div class="mb-2">
                                        <label class="form-label">Code</label>
                                        <input class="form-control"
                                            id="code<?= $d['id'] ?>"
                                            value="<?= htmlspecialchars($d['dept_code']) ?>">
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label">Name</label>
                                        <input class="form-control"
                                            id="name<?= $d['id'] ?>"
                                            value="<?= htmlspecialchars($d['dept_name']) ?>">
                                    </div>

                                    <div id="feedback<?= $d['id'] ?>" class="small"></div>

                                </div>

                                <div class="modal-footer">
                                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button class="btn btn-primary"
                                        onclick="updateDepartment(<?= $d['id'] ?>)">
                                        Save Changes
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                <?php endforeach ?>

            </tbody>
        </table>

        <a href="dashboard.php" class="btn btn-secondary">⬅ Back</a>
    </div>

    <!-- ADD MODAL -->
    <div class="modal fade" id="addDeptModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Add Department</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-2">
                        <label class="form-label">Code</label>
                        <input id="new_code" class="form-control">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Name</label>
                        <input id="new_name" class="form-control">
                    </div>

                    <div id="add-feedback" class="small"></div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" onclick="addDepartment()">Add</button>
                </div>

            </div>
        </div>
    </div>

    <script>
        function addDepartment() {
            const feedback = document.getElementById('add-feedback');

            const data = {
                code: document.getElementById('new_code').value,
                name: document.getElementById('new_name').value
            };

            feedback.innerHTML = 'Saving...';

            fetch('create_department_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams(data)
                })
                .then(r => r.json())
                .then(r => {
                    if (!r.success) {
                        feedback.innerHTML = '<span class="text-danger">Failed</span>';
                        return;
                    }
                    location.reload();
                })
                .catch(() => feedback.innerHTML = '<span class="text-danger">Server error</span>');
        }

        function updateDepartment(id) {
            const feedback = document.getElementById('feedback' + id);

            const data = {
                dept_id: id,
                code: document.getElementById('code' + id).value,
                name: document.getElementById('name' + id).value
            };

            feedback.innerHTML = 'Saving...';

            fetch('update_department_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams(data)
                })
                .then(r => r.json())
                .then(r => {
                    if (!r.success) {
                        feedback.innerHTML = '<span class="text-danger">Update failed</span>';
                        return;
                    }
                    location.reload();
                })
                .catch(() => feedback.innerHTML = '<span class="text-danger">Server error</span>');
        }

        function toggleDepartment(id) {
            if (!confirm('Change department status?')) return;

            fetch('toggle_department_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        dept_id: id
                    })
                })
                .then(r => r.json())
                .then(r => {
                    if (!r.success) {
                        alert('Action failed');
                        return;
                    }
                    location.reload();
                })
                .catch(() => alert('Server error'));
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>