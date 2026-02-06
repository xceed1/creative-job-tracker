<?php
require_once "../auth/guard.php";
requireRole(['USER']);
require_once "../config/db.php";
require_once "../config/db_context.php";
setDbContext($pdo);
require_once "../config/paths.php";

?>
<!DOCTYPE html>
<html>

<head>
    <title>Create New Job</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

    <h4 class="mb-4">Create New Job Request</h4>

    <form id="createForm" enctype="multipart/form-data">

        <!-- CLIENT -->
        <div class="mb-3">
            <label class="form-label">Client</label>
            <input type="text"
                   name="client_name"
                   class="form-control"
                   required>
        </div>

        <!-- PROJECT -->
        <div class="mb-3">
            <label class="form-label">Project Name</label>
            <input type="text"
                   name="project_name"
                   class="form-control"
                   required>
        </div>

        <!-- SUBJECT -->
        <div class="mb-3">
            <label class="form-label">Job Subject</label>
            <input type="text"
                   name="job_subject"
                   class="form-control"
                   required>
        </div>

        <!-- BRIEF -->
        <div class="mb-3">
            <label class="form-label">Brief</label>
            <textarea name="brief"
                      class="form-control"
                      rows="5"
                      required></textarea>
        </div>

        <!-- DUE DATE -->
        <div class="mb-3">
            <label class="form-label">Requested Due Date</label>
            <input type="date"
                   name="due_date"
                   class="form-control"
                   required>
        </div>

        <!-- RECEIVED FORMAT -->
        <div class="mb-3">
            <label class="form-label">Received Format</label>
            <input type="text"
                   name="received_format"
                   class="form-control"
                   placeholder="e.g. PDF, PPT, DOCX">
        </div>

        <!-- RECEIVED FILE -->
        <div class="mb-3">
            <label class="form-label">Received Format Attachment (Max 1MB)</label>
            <input type="file"
                   name="received_file"
                   class="form-control"
                   accept=".pdf,.doc,.docx,.ppt,.pptx,.zip">
        </div>

        <!-- REFERENCE -->
        <div class="mb-3">
            <label class="form-label">Reference Link</label>
            <input type="url"
                   name="reference_link"
                   class="form-control"
                   placeholder="https://">
        </div>

        <div id="feedback" class="mb-3"></div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                Create Job
            </button>
            <button type="button"
                    class="btn btn-secondary"
                    onclick="window.close()">
                Cancel
            </button>
        </div>

    </form>

</div>

<script>
document.getElementById('createForm').addEventListener('submit', e => {
    e.preventDefault();

    const form = e.target;
    const feedback = document.getElementById('feedback');
    const data = new FormData(form);

    feedback.innerHTML = 'Submitting...';

    fetch('create_job_api.php', {
        method: 'POST',
        body: data
    })
    .then(r => r.json())
    .then(r => {
        if (!r.success) {
            feedback.innerHTML =
                '<div class="alert alert-danger">' + (r.error || 'Failed') + '</div>';
            return;
        }

        feedback.innerHTML =
            '<div class="alert alert-success">Job created successfully</div>';

        setTimeout(() => {
            window.opener.location.reload();
            window.close();
        }, 700);
    })
    .catch(() => {
        feedback.innerHTML =
            '<div class="alert alert-danger">Server error</div>';
    });
});
</script>

</body>
</html>
