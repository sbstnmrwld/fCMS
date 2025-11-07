<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin' ?> - fCMS Verwaltung</title>

    <!-- Bootstrap CSS (aus Admin-Assets) -->
    <link href="<?= $adminAssets->bootstrapCss() ?>" rel="stylesheet">

    <!-- Bootstrap Icons (lokal) -->
    <link href="<?= $adminAssets->bootstrapIcons() ?>" rel="stylesheet">

    <!-- Admin CSS -->
    <link href="<?= $adminAssets->css('admin.css') ?>" rel="stylesheet">

    <!-- Module Assets (CSS) -->
    <?php if (!empty($moduleAssets['css'])): ?>
        <?php foreach ($moduleAssets['css'] as $cssFile): ?>
            <link href="<?= htmlspecialchars($cssFile) ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
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
                    <!-- Standard-Menüpunkte -->
                    <li class="nav-item">
                        <a class="nav-link<?= ($activeMenu ?? '') === 'dashboard' ? ' active' : '' ?>"
                           href="/admin">
                            <i class="bi bi-house-door me-1"></i>
                            <?= $lang->t('admin.dashboard') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($activeMenu ?? '') === 'pages' ? ' active' : '' ?>"
                           href="/admin/pages">
                            <i class="bi bi-file-earmark-text me-1"></i>
                            <?= $lang->t('admin.pages') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($activeMenu ?? '') === 'navigation' ? ' active' : '' ?>"
                           href="/admin/navigation">
                            <i class="bi bi-list-ul me-1"></i>
                            <?= $lang->t('admin.navigation') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($activeMenu ?? '') === 'media' ? ' active' : '' ?>"
                           href="/admin/media">
                            <i class="bi bi-images me-1"></i>
                            <?= $lang->t('admin.media') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($activeMenu ?? '') === 'blocks' ? ' active' : '' ?>"
                           href="/admin/blocks">
                            <i class="bi bi-boxes me-1"></i>
                            Blöcke
                        </a>
                    </li>
                    <!-- Module-Menü mit dynamischen Einträgen -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle<?= ($activeMenu ?? '') === 'modules' ? ' active' : '' ?>" href="#" id="modulesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-puzzle me-1"></i>
                            Module
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="modulesDropdown">
                            <li>
                                <a class="dropdown-item" href="/admin/modules">
                                    <i class="bi bi-puzzle me-1"></i> Modulverwaltung
                                </a>
                            </li>
                            <?php
                            // Dynamische Modul-Menüpunkte einfügen
                            if (isset($container)) {
                                $moduleManager = $container->get(FCMS\Core\ModuleManager::class);
                                $activeModules = $moduleManager->getActiveModules();
                                foreach ($activeModules as $moduleId) {
                                    $module = $moduleManager->getModule($moduleId);
                                    $config = $module ? $module->getConfig() : [];
                                    if ($module && !empty($config['menu'])) {
                                        $menu = $config['menu'];
                                        $icon = !empty($menu['icon']) ? $menu['icon'] : 'bi-puzzle';
                                        $title = !empty($menu['title']) ? $menu['title'] : ucfirst($moduleId);
                                        $route = !empty($config['routes'][0]) ? $config['routes'][0] : '/admin/modules';
                                        echo '<li><a class="dropdown-item" href="' . htmlspecialchars($route) . '" title="' . htmlspecialchars(json_encode($menu)) . '"><i class="bi ' . htmlspecialchars($icon) . ' me-1"></i> ' . htmlspecialchars($title) . '</a></li>';
                                    }
                                }
                                // Debug-Menüpunkt
                                echo '<li><hr class="dropdown-divider"></li>';
                                echo '<li><a class="dropdown-item text-danger" href="#" title="Debug: Aktive Module und Menüs" onclick="alert(\'Aktive Module: ' . htmlspecialchars(json_encode($activeModules)) . '\n\nMenü-Konfigurationen: ' . htmlspecialchars(json_encode(array_map(function($id) use ($moduleManager) { $m = $moduleManager->getModule($id); return $m ? $m->config['menu'] ?? [] : []; }, $activeModules))) . '\'); return false;">Debug: Module & Menüs</a></li>';
                            }
                            ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($activeMenu ?? '') === 'settings' ? ' active' : '' ?>"
                           href="/admin/settings">
                            <i class="bi bi-gear me-1"></i>
                            <?= $lang->t('admin.settings') ?>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown"
                           data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars($username ?? 'Admin') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="/" target="_blank">
                                    <i class="bi bi-box-arrow-up-right me-2"></i>
                                    Website anzeigen
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="/admin/logout">
                                    <i class="bi bi-box-arrow-right me-2"></i>
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

    <!-- Module Assets -->
    <?php if (!empty($moduleAssets['js'])): ?>
        <?php foreach ($moduleAssets['js'] as $jsFile): ?>
            <script src="<?= htmlspecialchars($jsFile) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- CSRF Token für AJAX -->
    <script>
        window.FCMS = {
            csrfToken: '<?= $csrfToken ?? '' ?>'
        };
    </script>
</body>
</html>
