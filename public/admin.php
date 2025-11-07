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
                    <h5 class="card-title">Seiten</h5>
                    <p class="dashboard-stat">' . count($allPages) . '</p>
                    <p class="text-muted">Gesamt</p>
                    <a href="/admin/pages" class="btn btn-primary btn-sm">Verwalten</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Veröffentlicht</h5>
                    <p class="dashboard-stat">' . count($publishedPages) . '</p>
                    <p class="text-muted">Live-Seiten</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Entwürfe</h5>
                    <p class="dashboard-stat">' . (count($allPages) - count($publishedPages)) . '</p>
                    <p class="text-muted">Nicht veröffentlicht</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h3>Willkommen im fCMS Admin-Bereich</h3>
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

// Pages-Verwaltung (Platzhalter)
$app->get('/pages', function (Request $request, Response $response) {
    $adminAssets = $this->get(AdminAssetManager::class);
    $lang = $this->get(LanguageManager::class);
    $auth = $this->get(AuthManager::class);
    $csrf = $this->get(CsrfManager::class);

    $placeholderContent = '<div class="container mt-4">
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading">🚧 In Entwicklung</h4>
            <p>Die Sektion <strong>Seiten</strong> ist aktuell noch nicht implementiert.</p>
            <hr>
            <p class="mb-0">Diese Funktion wird in einer zukünftigen Version verfügbar sein.</p>
        </div>
        <a href="/admin" class="btn btn-primary">← Zurück zum Dashboard</a>
    </div>';

    $html = renderAdminTemplate('layout', [
        'content' => $placeholderContent,
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

// Navigation-Verwaltung (Platzhalter)
$app->get('/navigation', function (Request $request, Response $response) {
    $adminAssets = $this->get(AdminAssetManager::class);
    $lang = $this->get(LanguageManager::class);
    $auth = $this->get(AuthManager::class);
    $csrf = $this->get(CsrfManager::class);

    $placeholderContent = '<div class="container mt-4">
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading">🚧 In Entwicklung</h4>
            <p>Die Sektion <strong>Navigation</strong> ist aktuell noch nicht implementiert.</p>
            <hr>
            <p class="mb-0">Diese Funktion wird in einer zukünftigen Version verfügbar sein.</p>
        </div>
        <a href="/admin" class="btn btn-primary">← Zurück zum Dashboard</a>
    </div>';

    $html = renderAdminTemplate('layout', [
        'content' => $placeholderContent,
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
