<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin' ?> - fCMS Verwaltung</title>
    
    <!-- Bootstrap CSS (aus Admin-Assets) -->
    <link href="<?= $adminAssets->bootstrapCss() ?>" rel="stylesheet">
    
    <!-- Admin CSS -->
    <link href="<?= $adminAssets->css('admin.css') ?>" rel="stylesheet">
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin">
                <strong>fCMS</strong> Verwaltung
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link<?= ($activeMenu ?? '') === 'dashboard' ? ' active' : '' ?>" 
                           href="/admin">
                            <?= $lang->t('admin.dashboard') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($activeMenu ?? '') === 'pages' ? ' active' : '' ?>" 
                           href="/admin/pages">
                            <?= $lang->t('admin.pages') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($activeMenu ?? '') === 'navigation' ? ' active' : '' ?>" 
                           href="/admin/navigation">
                            <?= $lang->t('admin.navigation') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($activeMenu ?? '') === 'media' ? ' active' : '' ?>" 
                           href="/admin/media">
                            <?= $lang->t('admin.media') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($activeMenu ?? '') === 'settings' ? ' active' : '' ?>" 
                           href="/admin/settings">
                            <?= $lang->t('admin.settings') ?>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" 
                           data-bs-toggle="dropdown">
                            <?= htmlspecialchars($username ?? 'Admin') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="/" target="_blank">
                                    Website anzeigen
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="/admin/logout">
                                    <?= $lang->t('admin.logout') ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hauptinhalt -->
    <main class="container-fluid py-4">
        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $message['type'] ?? 'info' ?> alert-dismissible fade show">
                <?= htmlspecialchars($message['text']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>

    <!-- Bootstrap JS (aus Admin-Assets) -->
    <script src="<?= $adminAssets->bootstrapJs() ?>"></script>
    
    <!-- Admin JS -->
    <script src="<?= $adminAssets->js('admin.js') ?>"></script>
    
    <!-- CSRF Token für AJAX -->
    <script>
        window.FCMS = {
            csrfToken: '<?= $csrfToken ?? '' ?>'
        };
    </script>
</body>
</html>
