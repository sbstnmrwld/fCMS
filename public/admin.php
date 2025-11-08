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
    ThemeAssetManager,
    SettingsManager,
    Validator
};
use FCMS\Exceptions\{
    ValidationException,
    NotFoundException,
    StorageException
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

$container->set(SettingsManager::class, function ($c) {
    $config = $c->get('config');
    return new SettingsManager($config['paths']['content']);
});

$container->set(BlockRegistry::class, function ($c) {
    $config = $c->get('config');
    $registry = new BlockRegistry($config['paths']['blocks']);
    $registry->autoDiscoverBlocks();
    return $registry;
});

$container->set(\FCMS\Core\ModuleManager::class, function ($c) {
    $config = $c->get('config');
    $moduleManager = new \FCMS\Core\ModuleManager(
        $config['paths']['modules'],
        $config['paths']['content']
    );
    $moduleManager->discoverModules();
    return $moduleManager;
});

// Slim App erstellen
AppFactory::setContainer($container);
$app = AppFactory::create();

// BasePath setzen - .htaccess leitet /admin/* zu admin.php um
// aber der REQUEST_URI enthält noch /admin/*
$app->setBasePath('/admin');

// Module booten (nach App-Erstellung)
$moduleManager = $container->get(\FCMS\Core\ModuleManager::class);
$moduleManager->bootActiveModules($app, $container);

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

    // Lade Modul-Assets für Admin-Bereich
    $moduleManager = $container->get(\FCMS\Core\ModuleManager::class);
    $moduleAssets = [
        'css' => [],
        'js' => []
    ];

    // Hole aktive Module und deren Assets
    foreach ($moduleManager->getActiveModules() as $moduleName) {
        $module = $moduleManager->getModule($moduleName);
        if ($module) {
            $assets = $module->getAdminAssets();
            if (!empty($assets['css'])) {
                $moduleAssets['css'] = array_merge($moduleAssets['css'], $assets['css']);
            }
            if (!empty($assets['js'])) {
                $moduleAssets['js'] = array_merge($moduleAssets['js'], $assets['js']);
            }
        }
    }

    extract($data);
    // moduleAssets ist jetzt auch als Variable verfügbar

    ob_start();
    include __DIR__ . '/../admin/templates/' . $template . '.php';
    return ob_get_clean();
}

// ============================================================================
// CONTROLLER REGISTRATION
// ============================================================================

// Register Controllers in DI Container
use FCMS\Controllers\{
    AuthController,
    PageController,
    DashboardController,
    SettingsController,
    ModuleController,
    MediaController,
    NavigationController,
    ThemeController,
    BlockController
};

$container->set(AuthController::class, function ($c) {
    return new AuthController($c);
});

$container->set(PageController::class, function ($c) {
    return new PageController($c);
});

$container->set(DashboardController::class, function ($c) {
    return new DashboardController($c);
});

$container->set(SettingsController::class, function ($c) {
    return new SettingsController($c);
});

$container->set(ModuleController::class, function ($c) {
    return new ModuleController($c);
});

$container->set(MediaController::class, function ($c) {
    return new MediaController($c);
});

$container->set(NavigationController::class, function ($c) {
    return new NavigationController($c);
});

$container->set(ThemeController::class, function ($c) {
    return new ThemeController($c);
});

$container->set(BlockController::class, function ($c) {
    return new BlockController($c);
});

// ============================================================================
// AUTHENTICATION ROUTES (via AuthController)
// ============================================================================

$app->get('/login', [AuthController::class, 'showLogin']);
$app->post('/login', [AuthController::class, 'login']);
$app->get('/logout', [AuthController::class, 'logout']);

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
// ============================================================================
// DASHBOARD ROUTES (via DashboardController)
// ============================================================================

$app->get('', [DashboardController::class, 'index'])->add($authMiddleware);
$app->get('/', [DashboardController::class, 'index'])->add($authMiddleware);

// ============================================================================
// PAGE ROUTES (via PageController)
// ============================================================================

$app->get('/pages', [PageController::class, 'index'])->add($authMiddleware);
$app->get('/pages/new', [PageController::class, 'create'])->add($authMiddleware);
$app->post('/pages/create', [PageController::class, 'store'])->add($authMiddleware);
$app->get('/pages/edit/{slug}', [PageController::class, 'edit'])->add($authMiddleware);
$app->post('/pages/update/{slug}', [PageController::class, 'update'])->add($authMiddleware);
$app->get('/pages/delete/{slug}', [PageController::class, 'delete'])->add($authMiddleware);

// ============================================================================
// MEDIA ROUTES (via MediaController)
// ============================================================================

$app->get('/media', [MediaController::class, 'index'])->add($authMiddleware);

// ============================================================================
// NAVIGATION ROUTES (via NavigationController)
// ============================================================================

$app->get('/navigation', [NavigationController::class, 'index'])->add($authMiddleware);

// ============================================================================
// THEME ROUTES (via ThemeController)
// ============================================================================

$app->get('/themes', [ThemeController::class, 'index'])->add($authMiddleware);

// ============================================================================
// SETTINGS ROUTES (via SettingsController)
// ============================================================================

$app->get('/settings', [SettingsController::class, 'index'])->add($authMiddleware);
$app->post('/settings/update', [SettingsController::class, 'update'])->add($authMiddleware);

// ============================================================================
// BLOCK ROUTES (via BlockController)
// ============================================================================

$app->get('/blocks', [BlockController::class, 'index'])->add($authMiddleware);

// ============================================================================
// MODULE ROUTES (via ModuleController)
// ============================================================================

$app->get('/modules', [ModuleController::class, 'index'])->add($authMiddleware);
$app->get('/modules/activate/{moduleId}', [ModuleController::class, 'activate'])->add($authMiddleware);
$app->get('/modules/deactivate/{moduleId}', [ModuleController::class, 'deactivate'])->add($authMiddleware);

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
