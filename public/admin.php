<?php
/**
 * fCMS - Admin Entry Point
 *
 * Verarbeitet alle Admin-Requests mit Session-basierter Authentifizierung.
 */

declare(strict_types=1);

// Autoloader
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use FCMS\Core\{
    SessionManager,
    CsrfManager,
    AuthManager,
    AdminAssetManager,
    ContentManager,
    LanguageManager,
    ThemeManager,
    ThemeAssetManager
};
use FCMS\Blocks\BlockRegistry;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Konfiguration laden
$config = require __DIR__ . '/../config/config.php';

// Fehlerbehandlung
if ($config['debug']['enabled']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// Dependency Injection Container
$container = new Container();

// Services registrieren
$container->set('config', $config);

$container->set(SessionManager::class, function ($c) {
    $config = $c->get('config');
    return new SessionManager($config['session']);
});

$container->set(CsrfManager::class, function ($c) {
    return new CsrfManager($c->get(SessionManager::class));
});

$container->set(AuthManager::class, function ($c) {
    $config = $c->get('config');
    return new AuthManager($c->get(SessionManager::class), $config);
});

$container->set(AdminAssetManager::class, function ($c) {
    $config = $c->get('config');
    return new AdminAssetManager(
        $config['paths']['admin'],
        $config['site']['url']
    );
});

$container->set(LanguageManager::class, function ($c) {
    $config = $c->get('config');
    return new LanguageManager(
        $config['paths']['languages'],
        $config['site']['language']
    );
});

$container->set(ContentManager::class, function ($c) {
    $config = $c->get('config');
    return new ContentManager($config['paths']['content']);
});

$container->set(BlockRegistry::class, function ($c) {
    $config = $c->get('config');
    $registry = new BlockRegistry($config['paths']['blocks']);
    $registry->autoDiscoverBlocks();
    return $registry;
});

// Slim App erstellen
AppFactory::setContainer($container);
$app = AppFactory::create();

// BasePath setzen - .htaccess leitet /admin/* zu admin.php um
// aber der REQUEST_URI enthält noch /admin/*
$app->setBasePath('/admin');

// Debug-Middleware (nur mit Debug-Modus)
if ($config['debug']['enabled']) {
    $app->add(function (Request $request, $handler) {
        error_log('REQUEST_URI: ' . $request->getUri()->getPath());
        error_log('SCRIPT_NAME: ' . ($_SERVER['SCRIPT_NAME'] ?? 'not set'));
        error_log('REQUEST_METHOD: ' . $request->getMethod());
        return $handler->handle($request);
    });
}

// Session starten für alle Requests
$app->add(function (Request $request, $handler) {
    $session = $this->get(SessionManager::class);
    $session->start();
    return $handler->handle($request);
});

// Helper-Funktion für Template-Rendering
function renderAdminTemplate(string $template, array $data, $container): string
{
    $adminAssets = $container->get(AdminAssetManager::class);
    $lang = $container->get(LanguageManager::class);
    $config = $container->get('config');

    extract($data);

    ob_start();
    include __DIR__ . '/../admin/templates/' . $template . '.php';
    return ob_get_clean();
}

// Login-Seite
$app->get('/login', function (Request $request, Response $response) {
    $auth = $this->get(AuthManager::class);

    if ($auth->isAuthenticated()) {
        return $response->withHeader('Location', '/admin')->withStatus(302);
    }

    $csrf = $this->get(CsrfManager::class);
    $adminAssets = $this->get(AdminAssetManager::class);
    $lang = $this->get(LanguageManager::class);
    $config = $this->get('config');

    $html = renderAdminTemplate('login', [
        'csrfField' => $csrf->getTokenField(),
        'adminAssets' => $adminAssets,
        'lang' => $lang,
        'config' => $config,
    ], $this);

    $response->getBody()->write($html);
    return $response;
});

// Login-Handler
$app->post('/login', function (Request $request, Response $response) {
    $auth = $this->get(AuthManager::class);
    $csrf = $this->get(CsrfManager::class);

    $data = $request->getParsedBody();

    if (!$csrf->validateRequest($data)) {
        $response->getBody()->write('CSRF-Validierung fehlgeschlagen');
        return $response->withStatus(403);
    }

    $lockoutTime = $auth->getLockoutTimeRemaining();

    if ($lockoutTime > 0) {
        $adminAssets = $this->get(AdminAssetManager::class);
        $lang = $this->get(LanguageManager::class);
        $config = $this->get('config');

        $html = renderAdminTemplate('login', [
            'csrfField' => $csrf->getTokenField(),
            'lockoutTime' => $lockoutTime,
            'adminAssets' => $adminAssets,
            'lang' => $lang,
            'config' => $config,
        ], $this);

        $response->getBody()->write($html);
        return $response;
    }

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if ($auth->attempt($username, $password)) {
        return $response->withHeader('Location', '/admin')->withStatus(302);
    }

    $adminAssets = $this->get(AdminAssetManager::class);
    $lang = $this->get(LanguageManager::class);
    $config = $this->get('config');

    $html = renderAdminTemplate('login', [
        'csrfField' => $csrf->getTokenField(),
        'error' => $lang->t('login.error'),
        'adminAssets' => $adminAssets,
        'lang' => $lang,
        'config' => $config,
    ], $this);

    $response->getBody()->write($html);
    return $response;
});

// Logout
$app->get('/logout', function (Request $request, Response $response) {
    $auth = $this->get(AuthManager::class);
    $auth->logout();
    return $response->withHeader('Location', '/admin/login')->withStatus(302);
});

// Auth-Middleware für geschützte Routen
$authMiddleware = function (Request $request, $handler) {
    $auth = $this->get(AuthManager::class);

    if (!$auth->isAuthenticated()) {
        $response = new \Slim\Psr7\Response();
        return $response->withHeader('Location', '/admin/login')->withStatus(302);
    }

    return $handler->handle($request);
};

// Dashboard (Root-Route im /admin Bereich) - sowohl mit als auch ohne trailing slash
$dashboardHandler = function (Request $request, Response $response) {
    $contentManager = $this->get(ContentManager::class);
    $adminAssets = $this->get(AdminAssetManager::class);
    $lang = $this->get(LanguageManager::class);
    $auth = $this->get(AuthManager::class);
    $csrf = $this->get(CsrfManager::class);

    $allPages = $contentManager->getAllPages();
    $publishedPages = $contentManager->getPublishedPages();

    $dashboardContent = '<div class="row">
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-file-earmark-text fs-1 text-primary me-3"></i>
                        <div>
                            <h5 class="card-title mb-0">Seiten</h5>
                            <p class="dashboard-stat mb-0">' . count($allPages) . '</p>
                        </div>
                    </div>
                    <p class="text-muted">Gesamt</p>
                    <a href="/admin/pages" class="btn btn-primary btn-sm">
                        <i class="bi bi-arrow-right me-1"></i>Verwalten
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-check-circle fs-1 text-success me-3"></i>
                        <div>
                            <h5 class="card-title mb-0">Veröffentlicht</h5>
                            <p class="dashboard-stat mb-0">' . count($publishedPages) . '</p>
                        </div>
                    </div>
                    <p class="text-muted">Live-Seiten</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-pencil-square fs-1 text-warning me-3"></i>
                        <div>
                            <h5 class="card-title mb-0">Entwürfe</h5>
                            <p class="dashboard-stat mb-0">' . (count($allPages) - count($publishedPages)) . '</p>
                        </div>
                    </div>
                    <p class="text-muted">Nicht veröffentlicht</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h3><i class="bi bi-house-door me-2"></i>Willkommen im fCMS Admin-Bereich</h3>
            <p>Verwalten Sie hier Ihre Website-Inhalte, Navigation und Einstellungen.</p>
        </div>
    </div>';

    $html = renderAdminTemplate('layout', [
        'content' => $dashboardContent,
        'title' => $lang->t('admin.dashboard'),
        'activeMenu' => 'dashboard',
        'adminAssets' => $adminAssets,
        'lang' => $lang,
        'username' => $auth->getUsername(),
        'csrfToken' => $csrf->getToken(),
    ], $this);

    $response->getBody()->write($html);
    return $response;
};

// Registriere Dashboard für beide Pfade (mit und ohne trailing slash)
$app->get('', $dashboardHandler)->add($authMiddleware);
$app->get('/', $dashboardHandler)->add($authMiddleware);

// ============================================================================
// PAGES VERWALTUNG
// ============================================================================

// Pages-Übersicht
$app->get('/pages', function (Request $request, Response $response) {
    $contentManager = $this->get(ContentManager::class);
    $adminAssets = $this->get(AdminAssetManager::class);
    $lang = $this->get(LanguageManager::class);
    $auth = $this->get(AuthManager::class);
    $csrf = $this->get(CsrfManager::class);

    $allPages = $contentManager->getAllPages();

    // Tabelle mit allen Seiten
    $pagesTable = '<div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th><i class="bi bi-file-text me-1"></i>Titel</th>
                    <th><i class="bi bi-link-45deg me-1"></i>Slug</th>
                    <th><i class="bi bi-circle-fill me-1"></i>Status</th>
                    <th><i class="bi bi-calendar me-1"></i>Erstellt</th>
                    <th><i class="bi bi-gear me-1"></i>Aktionen</th>
                </tr>
            </thead>
            <tbody>';

    if (empty($allPages)) {
        $pagesTable .= '<tr><td colspan="5" class="text-center text-muted">Noch keine Seiten vorhanden</td></tr>';
    } else {
        foreach ($allPages as $page) {
            $statusBadge = $page['status'] === 'published'
                ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Veröffentlicht</span>'
                : '<span class="badge bg-secondary"><i class="bi bi-pencil-square me-1"></i>Entwurf</span>';

            $createdDate = date('d.m.Y H:i', $page['created'] ?? time());

            $pagesTable .= '<tr>
                <td><strong>' . htmlspecialchars($page['title']) . '</strong></td>
                <td><code>' . htmlspecialchars($page['slug']) . '</code></td>
                <td>' . $statusBadge . '</td>
                <td>' . $createdDate . '</td>
                <td>
                    <a href="/admin/pages/edit/' . urlencode($page['slug']) . '" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil me-1"></i>Bearbeiten
                    </a>
                    <a href="/admin/pages/delete/' . urlencode($page['slug']) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Seite wirklich löschen?\')">
                        <i class="bi bi-trash me-1"></i>Löschen
                    </a>
                </td>
            </tr>';
        }
    }

    $pagesTable .= '</tbody></table></div>';

    $pagesContent = '<div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-file-earmark-text me-2"></i>Seiten verwalten</h2>
            <a href="/admin/pages/new" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i>Neue Seite
            </a>
        </div>
        ' . $pagesTable . '
    </div>';

    $html = renderAdminTemplate('layout', [
        'content' => $pagesContent,
        'title' => 'Seiten',
        'activeMenu' => 'pages',
        'adminAssets' => $adminAssets,
        'lang' => $lang,
        'username' => $auth->getUsername(),
        'csrfToken' => $csrf->getToken(),
    ], $this);

    $response->getBody()->write($html);
    return $response;
})->add($authMiddleware);

// Neue Seite erstellen (Formular)
$app->get('/pages/new', function (Request $request, Response $response) {
    $adminAssets = $this->get(AdminAssetManager::class);
    $lang = $this->get(LanguageManager::class);
    $auth = $this->get(AuthManager::class);
    $csrf = $this->get(CsrfManager::class);

    $formContent = '
    <link rel="stylesheet" href="' . $adminAssets->css('block-editor.css') . '">

    <div class="container-fluid mt-4">
        <h2><i class="bi bi-file-earmark-plus me-2"></i>Neue Seite erstellen</h2>

        <form method="POST" action="/admin/pages/create" class="mt-4" id="page-form">
            ' . $csrf->getTokenField() . '

            <div class="row">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label for="title" class="form-label"><i class="bi bi-type me-1"></i>Titel *</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label"><i class="bi bi-link-45deg me-1"></i>URL-Slug</label>
                        <input type="text" class="form-control" id="slug" name="slug" placeholder="Wird automatisch generiert">
                        <small class="form-text text-muted">Leer lassen für automatische Generierung aus dem Titel</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="bi bi-layout-text-window me-1"></i>Inhalt</label>
                        <div id="block-editor-container"></div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header"><strong><i class="bi bi-send me-1"></i>Veröffentlichung</strong></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft"><i class="bi bi-pencil-square"></i>Entwurf</option>
                                    <option value="published"><i class="bi bi-check-circle"></i>Veröffentlicht</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><strong><i class="bi bi-list-ul me-1"></i>Navigation</strong></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="nav_label" class="form-label"><i class="bi bi-tag me-1"></i>Menü-Eintrag</label>
                                <input type="text" class="form-control" id="nav_label" name="nav_label" placeholder="Leer lassen um Seitentitel zu verwenden">
                                <small class="form-text text-muted">Wird in Navigationsmenüs angezeigt</small>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="nav_main" name="nav_main" value="1">
                                <label class="form-check-label" for="nav_main">
                                    <i class="bi bi-menu-button-wide me-1"></i>Hauptnavigation
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="nav_footer" name="nav_footer" value="1">
                                <label class="form-check-label" for="nav_footer">
                                    <i class="bi bi-menu-down me-1"></i>Footer-Navigation
                                </label>
                            </div>
                            <div class="mt-3">
                                <label for="nav_order" class="form-label"><i class="bi bi-arrow-down-up me-1"></i>Reihenfolge</label>
                                <input type="number" class="form-control" id="nav_order" name="nav_order" value="0" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><strong><i class="bi bi-search me-1"></i>SEO</strong></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="meta_description" class="form-label"><i class="bi bi-text-paragraph me-1"></i>Meta Description</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="meta_keywords" class="form-label"><i class="bi bi-tags me-1"></i>Keywords</label>
                                <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" placeholder="keyword1, keyword2">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Seite erstellen
                </button>
                <a href="/admin/pages" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-1"></i>Abbrechen
                </a>
            </div>
        </form>
    </div>

    <script src="' . $adminAssets->js('block-editor.js') . '"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            blockEditor = new BlockEditor("block-editor-container");
        });
    </script>';

    $html = renderAdminTemplate('layout', [
        'content' => $formContent,
        'title' => 'Neue Seite',
        'activeMenu' => 'pages',
        'adminAssets' => $adminAssets,
        'lang' => $lang,
        'username' => $auth->getUsername(),
        'csrfToken' => $csrf->getToken(),
    ], $this);

    $response->getBody()->write($html);
    return $response;
})->add($authMiddleware);

// Neue Seite speichern (POST)
$app->post('/pages/create', function (Request $request, Response $response) {
    $contentManager = $this->get(ContentManager::class);
    $csrf = $this->get(CsrfManager::class);

    $data = $request->getParsedBody();

    // CSRF-Validierung
    if (!$csrf->validateToken($data['csrf_token'] ?? '')) {
        $response->getBody()->write('CSRF-Token ungültig');
        return $response->withStatus(403);
    }

    // Parse Blocks JSON
    $sections = [];
    if (!empty($data['blocks_json'])) {
        $blocks = json_decode($data['blocks_json'], true);
        if (is_array($blocks)) {
            $sections = $blocks;
        }
    }

    // Fallback: wenn keine Blocks, aber Content vorhanden
    if (empty($sections) && !empty($data['content'])) {
        $sections = [
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => $data['content']
                ]
            ]
        ];
    }

    // Seite erstellen
    $pageData = [
        'title' => $data['title'] ?? 'Neue Seite',
        'slug' => $data['slug'] ?? '',
        'status' => $data['status'] ?? 'draft',
        'sections' => $sections,
        'nav_main' => isset($data['nav_main']),
        'nav_footer' => isset($data['nav_footer']),
        'nav_order' => (int)($data['nav_order'] ?? 0),
        'nav_label' => trim($data['nav_label'] ?? ''),
        'meta_description' => $data['meta_description'] ?? '',
        'meta_keywords' => $data['meta_keywords'] ?? '',
    ];

    $success = $contentManager->createPage($pageData);

    if ($success) {
        return $response->withHeader('Location', '/admin/pages')->withStatus(302);
    } else {
        $response->getBody()->write('Fehler beim Erstellen der Seite');
        return $response->withStatus(500);
    }
})->add($authMiddleware);

// Seite bearbeiten (Formular)
$app->get('/pages/edit/{slug}', function (Request $request, Response $response, array $args) {
    $contentManager = $this->get(ContentManager::class);
    $adminAssets = $this->get(AdminAssetManager::class);
    $lang = $this->get(LanguageManager::class);
    $auth = $this->get(AuthManager::class);
    $csrf = $this->get(CsrfManager::class);

    $slug = $args['slug'];
    $page = $contentManager->getPage($slug);

    if (!$page) {
        $response->getBody()->write('Seite nicht gefunden');
        return $response->withStatus(404);
    }

    // Bereite Blocks JSON vor
    $blocksJson = json_encode($page['sections'] ?? []);

    $formContent = '
    <link rel="stylesheet" href="' . $adminAssets->css('block-editor.css') . '">
    <input type="hidden" id="initial_blocks_data" value=\'' . htmlspecialchars($blocksJson, ENT_QUOTES) . '\'>

    <div class="container-fluid mt-4">
        <h2><i class="bi bi-pencil-square me-2"></i>Seite bearbeiten: ' . htmlspecialchars($page['title']) . '</h2>

        <form method="POST" action="/admin/pages/update/' . urlencode($slug) . '" class="mt-4" id="page-form">
            ' . $csrf->getTokenField() . '

            <div class="row">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label for="title" class="form-label"><i class="bi bi-type me-1"></i>Titel *</label>
                        <input type="text" class="form-control" id="title" name="title" value="' . htmlspecialchars($page['title']) . '" required>
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label"><i class="bi bi-link-45deg me-1"></i>URL-Slug</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="' . htmlspecialchars($page['slug']) . '" readonly>
                        <small class="form-text text-muted">Der Slug kann nicht geändert werden</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="bi bi-layout-text-window me-1"></i>Inhalt</label>
                        <div id="block-editor-container"></div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header"><strong><i class="bi bi-send me-1"></i>Veröffentlichung</strong></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft"' . ($page['status'] === 'draft' ? ' selected' : '') . '>Entwurf</option>
                                    <option value="published"' . ($page['status'] === 'published' ? ' selected' : '') . '>Veröffentlicht</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><strong><i class="bi bi-list-ul me-1"></i>Navigation</strong></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="nav_label" class="form-label"><i class="bi bi-tag me-1"></i>Menü-Eintrag</label>
                                <input type="text" class="form-control" id="nav_label" name="nav_label" value="' . htmlspecialchars($page['navigation']['label'] ?? '') . '" placeholder="Leer lassen um Seitentitel zu verwenden">
                                <small class="form-text text-muted">Wird in Navigationsmenüs angezeigt</small>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="nav_main" name="nav_main" value="1"' . ($page['navigation']['main'] ?? false ? ' checked' : '') . '>
                                <label class="form-check-label" for="nav_main">
                                    <i class="bi bi-menu-button-wide me-1"></i>Hauptnavigation
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="nav_footer" name="nav_footer" value="1"' . ($page['navigation']['footer'] ?? false ? ' checked' : '') . '>
                                <label class="form-check-label" for="nav_footer">
                                    <i class="bi bi-menu-down me-1"></i>Footer-Navigation
                                </label>
                            </div>
                            <div class="mt-3">
                                <label for="nav_order" class="form-label"><i class="bi bi-arrow-down-up me-1"></i>Reihenfolge</label>
                                <input type="number" class="form-control" id="nav_order" name="nav_order" value="' . ($page['navigation']['order'] ?? 0) . '" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><strong><i class="bi bi-search me-1"></i>SEO</strong></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="meta_description" class="form-label"><i class="bi bi-text-paragraph me-1"></i>Meta Description</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" rows="3">' . htmlspecialchars($page['meta']['description'] ?? '') . '</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="meta_keywords" class="form-label"><i class="bi bi-tags me-1"></i>Keywords</label>
                                <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="' . htmlspecialchars($page['meta']['keywords'] ?? '') . '" placeholder="keyword1, keyword2">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Änderungen speichern
                </button>
                <a href="/admin/pages" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-1"></i>Abbrechen
                </a>
            </div>
        </form>
    </div>

    <script src="' . $adminAssets->js('block-editor.js') . '"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            blockEditor = new BlockEditor("block-editor-container");
        });
    </script>';

    $html = renderAdminTemplate('layout', [
        'content' => $formContent,
        'title' => 'Seite bearbeiten',
        'activeMenu' => 'pages',
        'adminAssets' => $adminAssets,
        'lang' => $lang,
        'username' => $auth->getUsername(),
        'csrfToken' => $csrf->getToken(),
    ], $this);

    $response->getBody()->write($html);
    return $response;
})->add($authMiddleware);

// Seite aktualisieren (POST)
$app->post('/pages/update/{slug}', function (Request $request, Response $response, array $args) {
    $contentManager = $this->get(ContentManager::class);
    $csrf = $this->get(CsrfManager::class);

    $slug = $args['slug'];
    $data = $request->getParsedBody();

    // CSRF-Validierung
    if (!$csrf->validateToken($data['csrf_token'] ?? '')) {
        $response->getBody()->write('CSRF-Token ungültig');
        return $response->withStatus(403);
    }

    // Parse Blocks JSON
    $sections = [];
    if (!empty($data['blocks_json'])) {
        $blocks = json_decode($data['blocks_json'], true);
        if (is_array($blocks)) {
            $sections = $blocks;
        }
    }

    // Fallback: wenn keine Blocks, aber Content vorhanden
    if (empty($sections) && !empty($data['content'])) {
        $sections = [
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => $data['content']
                ]
            ]
        ];
    }

    // Seite aktualisieren
    $pageData = [
        'title' => $data['title'] ?? 'Neue Seite',
        'status' => $data['status'] ?? 'draft',
        'sections' => $sections,
        'nav_main' => isset($data['nav_main']),
        'nav_footer' => isset($data['nav_footer']),
        'nav_order' => (int)($data['nav_order'] ?? 0),
        'nav_label' => trim($data['nav_label'] ?? ''),
        'meta_description' => $data['meta_description'] ?? '',
        'meta_keywords' => $data['meta_keywords'] ?? '',
    ];

    $success = $contentManager->updatePage($slug, $pageData);

    if ($success) {
        return $response->withHeader('Location', '/admin/pages')->withStatus(302);
    } else {
        $response->getBody()->write('Fehler beim Aktualisieren der Seite');
        return $response->withStatus(500);
    }
})->add($authMiddleware);

// Seite löschen
$app->get('/pages/delete/{slug}', function (Request $request, Response $response, array $args) {
    $contentManager = $this->get(ContentManager::class);

    $slug = $args['slug'];
    $success = $contentManager->deletePage($slug);

    if ($success) {
        return $response->withHeader('Location', '/admin/pages')->withStatus(302);
    } else {
        $response->getBody()->write('Fehler beim Löschen der Seite');
        return $response->withStatus(500);
    }
})->add($authMiddleware);

// Media-Verwaltung (Platzhalter)
$app->get('/media', function (Request $request, Response $response) {
    $adminAssets = $this->get(AdminAssetManager::class);
    $lang = $this->get(LanguageManager::class);
    $auth = $this->get(AuthManager::class);
    $csrf = $this->get(CsrfManager::class);

    $placeholderContent = '<div class="container mt-4">
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading">🚧 In Entwicklung</h4>
            <p>Die Sektion <strong>Medien</strong> ist aktuell noch nicht implementiert.</p>
            <hr>
            <p class="mb-0">Diese Funktion wird in einer zukünftigen Version verfügbar sein.</p>
        </div>
        <a href="/admin" class="btn btn-primary">← Zurück zum Dashboard</a>
    </div>';

    $html = renderAdminTemplate('layout', [
        'content' => $placeholderContent,
        'title' => 'Medien',
        'activeMenu' => 'media',
        'adminAssets' => $adminAssets,
        'lang' => $lang,
        'username' => $auth->getUsername(),
        'csrfToken' => $csrf->getToken(),
    ], $this);

    $response->getBody()->write($html);
    return $response;
})->add($authMiddleware);

// ============================================================================
// NAVIGATION VERWALTUNG
// ============================================================================

// Navigation-Übersicht
$app->get('/navigation', function (Request $request, Response $response) {
    $contentManager = $this->get(ContentManager::class);
    $adminAssets = $this->get(AdminAssetManager::class);
    $lang = $this->get(LanguageManager::class);
    $auth = $this->get(AuthManager::class);
    $csrf = $this->get(CsrfManager::class);

    $allPages = $contentManager->getAllPages();
    $mainNavPages = $contentManager->getNavigationPages('main');
    $footerNavPages = $contentManager->getNavigationPages('footer');

    // Hauptnavigation Tabelle
    $mainNavTable = '<div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-menu-button-wide me-2"></i>Hauptnavigation</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><i class="bi bi-grip-vertical me-1"></i>Reihenfolge</th>
                            <th><i class="bi bi-file-text me-1"></i>Seite</th>
                            <th><i class="bi bi-tag me-1"></i>Menü-Label</th>
                            <th><i class="bi bi-link-45deg me-1"></i>Slug</th>
                            <th><i class="bi bi-circle-fill me-1"></i>Status</th>
                            <th><i class="bi bi-gear me-1"></i>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>';

    if (empty($mainNavPages)) {
        $mainNavTable .= '<tr><td colspan="6" class="text-center text-muted">Keine Seiten in der Hauptnavigation</td></tr>';
    } else {
        foreach ($mainNavPages as $page) {
            $statusBadge = $page['status'] === 'published'
                ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Veröffentlicht</span>'
                : '<span class="badge bg-secondary"><i class="bi bi-pencil-square me-1"></i>Entwurf</span>';

            $label = ($page['navigation']['label'] ?? '') ?: $page['title'];

            $mainNavTable .= '<tr>
                <td><strong>' . ($page['navigation']['order'] ?? 0) . '</strong></td>
                <td>' . htmlspecialchars($page['title']) . '</td>
                <td>' . htmlspecialchars($label) . '</td>
                <td><code>' . htmlspecialchars($page['slug']) . '</code></td>
                <td>' . $statusBadge . '</td>
                <td>
                    <a href="/admin/pages/edit/' . urlencode($page['slug']) . '" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil me-1"></i>Bearbeiten
                    </a>
                </td>
            </tr>';
        }
    }

    $mainNavTable .= '</tbody></table>
            </div>
        </div>
    </div>';

    // Footer Navigation Tabelle
    $footerNavTable = '<div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-menu-down me-2"></i>Footer-Navigation</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><i class="bi bi-grip-vertical me-1"></i>Reihenfolge</th>
                            <th><i class="bi bi-file-text me-1"></i>Seite</th>
                            <th><i class="bi bi-tag me-1"></i>Menü-Label</th>
                            <th><i class="bi bi-link-45deg me-1"></i>Slug</th>
                            <th><i class="bi bi-circle-fill me-1"></i>Status</th>
                            <th><i class="bi bi-gear me-1"></i>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>';

    if (empty($footerNavPages)) {
        $footerNavTable .= '<tr><td colspan="6" class="text-center text-muted">Keine Seiten in der Footer-Navigation</td></tr>';
    } else {
        foreach ($footerNavPages as $page) {
            $statusBadge = $page['status'] === 'published'
                ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Veröffentlicht</span>'
                : '<span class="badge bg-secondary"><i class="bi bi-pencil-square me-1"></i>Entwurf</span>';

            $label = ($page['navigation']['label'] ?? '') ?: $page['title'];

            $footerNavTable .= '<tr>
                <td><strong>' . ($page['navigation']['order'] ?? 0) . '</strong></td>
                <td>' . htmlspecialchars($page['title']) . '</td>
                <td>' . htmlspecialchars($label) . '</td>
                <td><code>' . htmlspecialchars($page['slug']) . '</code></td>
                <td>' . $statusBadge . '</td>
                <td>
                    <a href="/admin/pages/edit/' . urlencode($page['slug']) . '" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil me-1"></i>Bearbeiten
                    </a>
                </td>
            </tr>';
        }
    }

    $footerNavTable .= '</tbody></table>
            </div>
        </div>
    </div>';

    // Verfügbare Seiten (nicht in Navigation)
    $availablePages = array_filter($allPages, function($page) {
        return !($page['navigation']['main'] ?? false) && !($page['navigation']['footer'] ?? false);
    });

    $availablePagesCard = '<div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-files me-2"></i>Verfügbare Seiten (nicht in Navigation)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><i class="bi bi-file-text me-1"></i>Seite</th>
                            <th><i class="bi bi-link-45deg me-1"></i>Slug</th>
                            <th><i class="bi bi-circle-fill me-1"></i>Status</th>
                            <th><i class="bi bi-gear me-1"></i>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>';

    if (empty($availablePages)) {
        $availablePagesCard .= '<tr><td colspan="4" class="text-center text-muted">Alle Seiten sind bereits einer Navigation zugeordnet</td></tr>';
    } else {
        foreach ($availablePages as $page) {
            $statusBadge = $page['status'] === 'published'
                ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Veröffentlicht</span>'
                : '<span class="badge bg-secondary"><i class="bi bi-pencil-square me-1"></i>Entwurf</span>';

            $availablePagesCard .= '<tr>
                <td>' . htmlspecialchars($page['title']) . '</td>
                <td><code>' . htmlspecialchars($page['slug']) . '</code></td>
                <td>' . $statusBadge . '</td>
                <td>
                    <a href="/admin/pages/edit/' . urlencode($page['slug']) . '" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil me-1"></i>Bearbeiten
                    </a>
                </td>
            </tr>';
        }
    }

    $availablePagesCard .= '</tbody></table>
            </div>
        </div>
    </div>';

    $navigationContent = '<div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-list-ul me-2"></i>Navigation verwalten</h2>
            <a href="/admin/pages/new" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i>Neue Seite erstellen
            </a>
        </div>

        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Hinweis:</strong> Um Seiten zur Navigation hinzuzufügen oder die Reihenfolge zu ändern,
            bearbeiten Sie die entsprechende Seite und passen Sie die Navigationseinstellungen an.
        </div>

        ' . $mainNavTable . '
        ' . $footerNavTable . '
        ' . $availablePagesCard . '
    </div>';

    $html = renderAdminTemplate('layout', [
        'content' => $navigationContent,
        'title' => 'Navigation',
        'activeMenu' => 'navigation',
        'adminAssets' => $adminAssets,
        'lang' => $lang,
        'username' => $auth->getUsername(),
        'csrfToken' => $csrf->getToken(),
    ], $this);

    $response->getBody()->write($html);
    return $response;
})->add($authMiddleware);

// Theme-Einstellungen (Platzhalter)
$app->get('/themes', function (Request $request, Response $response) {
    $adminAssets = $this->get(AdminAssetManager::class);
    $lang = $this->get(LanguageManager::class);
    $auth = $this->get(AuthManager::class);
    $csrf = $this->get(CsrfManager::class);

    $placeholderContent = '<div class="container mt-4">
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading">🚧 In Entwicklung</h4>
            <p>Die Sektion <strong>Themes</strong> ist aktuell noch nicht implementiert.</p>
            <hr>
            <p class="mb-0">Diese Funktion wird in einer zukünftigen Version verfügbar sein.</p>
        </div>
        <a href="/admin" class="btn btn-primary">← Zurück zum Dashboard</a>
    </div>';

    $html = renderAdminTemplate('layout', [
        'content' => $placeholderContent,
        'title' => 'Themes',
        'activeMenu' => 'themes',
        'adminAssets' => $adminAssets,
        'lang' => $lang,
        'username' => $auth->getUsername(),
        'csrfToken' => $csrf->getToken(),
    ], $this);

    $response->getBody()->write($html);
    return $response;
})->add($authMiddleware);

// System-Einstellungen (Platzhalter)
$app->get('/settings', function (Request $request, Response $response) {
    $adminAssets = $this->get(AdminAssetManager::class);
    $lang = $this->get(LanguageManager::class);
    $auth = $this->get(AuthManager::class);
    $csrf = $this->get(CsrfManager::class);

    $placeholderContent = '<div class="container mt-4">
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading">🚧 In Entwicklung</h4>
            <p>Die Sektion <strong>Einstellungen</strong> ist aktuell noch nicht implementiert.</p>
            <hr>
            <p class="mb-0">Diese Funktion wird in einer zukünftigen Version verfügbar sein.</p>
        </div>
        <a href="/admin" class="btn btn-primary">← Zurück zum Dashboard</a>
    </div>';

    $html = renderAdminTemplate('layout', [
        'content' => $placeholderContent,
        'title' => 'Einstellungen',
        'activeMenu' => 'settings',
        'adminAssets' => $adminAssets,
        'lang' => $lang,
        'username' => $auth->getUsername(),
        'csrfToken' => $csrf->getToken(),
    ], $this);

    $response->getBody()->write($html);
    return $response;
})->add($authMiddleware);

// Benutzer-Profil (Platzhalter)
$app->get('/profile', function (Request $request, Response $response) {
    $adminAssets = $this->get(AdminAssetManager::class);
    $lang = $this->get(LanguageManager::class);
    $auth = $this->get(AuthManager::class);
    $csrf = $this->get(CsrfManager::class);

    $placeholderContent = '<div class="container mt-4">
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading">🚧 In Entwicklung</h4>
            <p>Die Sektion <strong>Profil</strong> ist aktuell noch nicht implementiert.</p>
            <hr>
            <p class="mb-0">Diese Funktion wird in einer zukünftigen Version verfügbar sein.</p>
        </div>
        <a href="/admin" class="btn btn-primary">← Zurück zum Dashboard</a>
    </div>';

    $html = renderAdminTemplate('layout', [
        'content' => $placeholderContent,
        'title' => 'Profil',
        'activeMenu' => 'profile',
        'adminAssets' => $adminAssets,
        'lang' => $lang,
        'username' => $auth->getUsername(),
        'csrfToken' => $csrf->getToken(),
    ], $this);

    $response->getBody()->write($html);
    return $response;
})->add($authMiddleware);

// Error Handler
$errorMiddleware = $app->addErrorMiddleware(
    $config['debug']['enabled'],
    true,
    true
);

// App ausführen
$app->run();
