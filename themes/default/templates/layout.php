<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page) ? htmlspecialchars($page['title']) : 'Home' ?> - <?= htmlspecialchars($siteName ?? 'fCMS') ?></title>

    <?php if (isset($page['meta']['description']) && $page['meta']['description']): ?>
    <meta name="description" content="<?= htmlspecialchars($page['meta']['description']) ?>">
    <?php endif; ?>

    <?php if (isset($page['meta']['keywords']) && $page['meta']['keywords']): ?>
    <meta name="keywords" content="<?= htmlspecialchars($page['meta']['keywords']) ?>">
    <?php endif; ?>

    <!-- Bootstrap CSS (aus Theme-Assets) -->
    <link href="<?= $assets->bootstrapCss() ?>" rel="stylesheet">

    <!-- Theme CSS -->
    <link href="<?= $assets->css('style.css') ?>" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="/"><?= htmlspecialchars($siteName ?? 'fCMS') ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($navigation) && !empty($navigation)): ?>
                        <?php foreach ($navigation as $item): ?>
                            <li class="nav-item">
                                <a class="nav-link<?= (isset($page) && $page['slug'] === $item['slug']) ? ' active' : '' ?>"
                                   href="/<?= htmlspecialchars($item['slug']) ?>">
                                    <?= htmlspecialchars($item['title']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hauptinhalt -->
    <main class="py-5">
        <?= $content ?? '' ?>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName ?? 'fCMS') ?>. Alle Rechte vorbehalten.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <?php if (isset($footerNavigation) && !empty($footerNavigation)): ?>
                        <ul class="list-inline mb-0">
                            <?php foreach ($footerNavigation as $item): ?>
                                <li class="list-inline-item">
                                    <a href="/<?= htmlspecialchars($item['slug']) ?>" class="text-white">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS (aus Theme-Assets) -->
    <script src="<?= $assets->bootstrapJs() ?>"></script>

    <!-- Theme JS -->
    <script src="<?= $assets->js('main.js') ?>"></script>
</body>
</html>
