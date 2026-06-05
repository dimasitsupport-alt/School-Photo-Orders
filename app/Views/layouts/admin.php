<?php $appName = (require BASE_PATH . '/config/app.php')['name']; ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Admin') ?> - <?= e($appName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="admin-body">
    <nav class="navbar navbar-expand-lg app-navbar">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand" href="<?= e(url('/admin/dashboard')) ?>">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-nav-toggle data-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <a class="nav-link" href="<?= e(url('/admin/dashboard')) ?>">Dashboard</a>
                    <span class="nav-link text-white-50"><?= e(current_admin()['username'] ?? 'admin') ?></span>
                    <form method="post" action="<?= e(url('/admin/logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-light" type="submit">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="admin-shell">
        <div class="container-fluid px-lg-4">
            <?php if ($message = flash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= e($message) ?>
                    <button type="button" class="btn-close" data-dismiss-alert aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($message = flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= e($message) ?>
                    <button type="button" class="btn-close" data-dismiss-alert aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </main>

    <?php clear_old(); ?>
    <script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
