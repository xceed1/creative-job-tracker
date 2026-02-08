<?php
require_once "../auth/guard.php";
requireRole(['ADMIN']);
require_once "../config/db.php";

$roles = $pdo->query("
    SELECT id, role_name
    FROM roles
    ORDER BY role_name
")->fetchAll();

$departments = $pdo->query("
    SELECT id, dept_name
    FROM departments
    WHERE is_active = 1
    ORDER BY dept_name
")->fetchAll();

$users = $pdo->query("
    SELECT 
        u.id, 
        u.full_name, 
        u.email, 
        u.is_active,
        r.id AS role_id,
        r.role_name,
        d.id AS dept_id,
        d.dept_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN departments d ON u.department_id = d.id
    ORDER BY u.full_name
")->fetchAll();
?>
<!DOCTYPE html>
<html>

<head>
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <span class="navbar-brand fw-semibold">Creative Job Tracker</span>
            <div class="d-flex gap-3 align-items-center">
                <span class="text-light small">Admin</span>
                <a href="../auth/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <h3>User Management</h3>

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
            Add User
        </button>

        <table class="table table-bordered table-hover bg-white shadow-sm">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th width="160">Actions</th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['full_name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['role_name']) ?></td>
                        <td><?= htmlspecialchars($u['dept_name'] ?? '-') ?></td>
                        <td>
                            <span class="badge bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>">
                                <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#editUser<?= $u['id'] ?>">
                                Edit
                            </button>

                            <button class="btn btn-sm btn-danger"
                                onclick="deleteUser(<?= $u['id'] ?>)">
                                Delete
                            </button>
                        </td>
                    </tr>

                    <!-- EDIT MODAL -->
                    <div class="modal fade" id="editUser<?= $u['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit User</h5>
                                    <button class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="mb-2">
                                        <label class="form-label">Full Name</label>
                                        <input class="form-control"
                                            id="edit_name_<?= $u['id'] ?>"
                                            value="<?= htmlspecialchars($u['full_name']) ?>">
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label">Email</label>
                                        <input class="form-control"
                                            value="<?= htmlspecialchars($u['email']) ?>"
                                            disabled>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label">Role</label>
                                        <select class="form-select" id="edit_role_<?= $u['id'] ?>">
                                            <?php foreach ($roles as $r): ?>
                                                <option value="<?= $r['id'] ?>"
                                                    <?= $u['role_id'] == $r['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($r['role_name']) ?>
                                                </option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label">Department</label>
                                        <select class="form-select" id="edit_dept_<?= $u['id'] ?>">
                                            <option value="">— None —</option>
                                            <?php foreach ($departments as $d): ?>
                                                <option value="<?= $d['id'] ?>"
                                                    <?= $u['dept_id'] == $d['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($d['dept_name']) ?>
                                                </option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" id="edit_active_<?= $u['id'] ?>">
                                            <option value="1" <?= $u['is_active'] ? 'selected' : '' ?>>Active</option>
                                            <option value="0" <?= !$u['is_active'] ? 'selected' : '' ?>>Inactive</option>
                                        </select>
                                    </div>

                                    <div id="edit_feedback_<?= $u['id'] ?>" class="small"></div>
                                </div>

                                <div class="modal-footer">
                                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button class="btn btn-primary"
                                        onclick="updateUser(<?= $u['id'] ?>)">
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

    <!-- ADD USER MODAL -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create User</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input name="full_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role_id" class="form-select" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>">
                                    <?= htmlspecialchars($r['role_name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">— None —</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>">
                                    <?= htmlspecialchars($d['dept_name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <div id="add-user-feedback" class="small"></div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" onclick="addUser()">Create User</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function addUser() {
            const modal = document.getElementById('addUserModal');
            const feedback = document.getElementById('add-user-feedback');

            const data = {
                full_name: modal.querySelector('[name="full_name"]').value,
                email: modal.querySelector('[name="email"]').value,
                role_id: modal.querySelector('[name="role_id"]').value,
                department_id: modal.querySelector('[name="department_id"]').value
            };

            feedback.innerHTML = 'Saving...';

            fetch('create_user_api.php', {
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

                .catch(() => {
                    feedback.innerHTML = '<span class="text-danger">Server error</span>';
                });
        }

        function updateUser(id) {
            const feedback = document.getElementById('edit_feedback_' + id);

            const data = {
                user_id: id,
                full_name: document.getElementById('edit_name_' + id).value,
                role_id: document.getElementById('edit_role_' + id).value,
                department_id: document.getElementById('edit_dept_' + id).value,
                is_active: document.getElementById('edit_active_' + id).value
            };

            feedback.innerHTML = 'Saving...';

            fetch('update_user_api.php', {
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

                    if (r.temp_password) {
                        alert(
                            'User activated successfully.\n\n' +
                            'Temporary Password:\n' +
                            r.temp_password +
                            '\n\nThe user will be forced to change it on first login.'
                        );
                    }

                    location.reload();
                })

                .catch(() => {
                    feedback.innerHTML = '<span class="text-danger">Server error</span>';
                });
        }

        function deleteUser(id) {
            if (!confirm('Deactivate this user?')) return;

            fetch('delete_user_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        user_id: id
                    })
                })
                .then(r => r.json())
                .then(r => {
                    if (!r.success) {
                        alert('Delete failed');
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