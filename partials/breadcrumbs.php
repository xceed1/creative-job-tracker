<?php
require_once __DIR__ . '/../helpers/breadcrumbs.php';

$breadcrumbs = buildBreadcrumbs($pageRole ?? '', $pageTitle ?? '');
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <?php foreach ($breadcrumbs as $crumb): ?>
            <?php if ($crumb['url']): ?>
                <li class="breadcrumb-item">
                    <a href="<?= $crumb['url'] ?>" class="text-decoration-none">
                        <?= htmlspecialchars($crumb['label']) ?>
                    </a>
                </li>
            <?php else: ?>
                <li class="breadcrumb-item active" aria-current="page">
                    <?= htmlspecialchars($crumb['label']) ?>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>
