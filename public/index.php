<?php
/**
 * fCMS - Frontend Entry Point
 *
 * Verarbeitet alle Frontend-Requests und rendert Seiten mit dem aktiven Theme.
 */

declare(strict_types=1);

// Autoloader
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use FCMS\Core\{
    ThemeManager,
    ThemeAssetManager,
    ContentManager,
    LanguageManager
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
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Dependency Injection Container
$container = new Container();

// Services registrieren
$container->set('config', $config);

$container->set(LanguageManager::class, function ($c) {
    $config = $c->get('config');
    return new LanguageManager(
        $config['paths']['languages'],
        $config['site']['language']
    );
});

$container->set(ThemeAssetManager::class, function ($c) {
    $config = $c->get('config');
    return new ThemeAssetManager(
        $config['paths']['themes'],
        $config['theme']['active'],
        $config['site']['url']
    );
});

$container->set(ThemeManager::class, function ($c) {
    $config = $c->get('config');
    return new ThemeManager(
        $config['paths']['themes'],
        $config['theme']['active'],
        $c->get(ThemeAssetManager::class),
        $c->get(LanguageManager::class)
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

$container->set(\FCMS\Core\ModuleManager::class, function ($c) {
    $config = $c->get('config');
    $moduleManager = new \FCMS\Core\ModuleManager($config['paths']['modules'], $config['paths']['content']);
    $moduleManager->discoverModules();
    return $moduleManager;
});

// Slim App erstellen
AppFactory::setContainer($container);
$app = AppFactory::create();

// Module booten (für öffentliche Routen)
$moduleManager = $container->get(\FCMS\Core\ModuleManager::class);
$moduleManager->bootActiveModules($app, $container);

// Öffentliche Modul-Routen registrieren
$activeModules = $moduleManager->getActiveModuleInstances($app, $container);
foreach ($activeModules as $module) {
    if (method_exists($module, 'registerPublicRoutes')) {
        $module->registerPublicRoutes($app, $container);
    }
}

// Route für Module-Assets
$app->get('/modules/{moduleName}/assets/{path:.*}', function (Request $request, Response $response, array $args) {
    $moduleName = $args['moduleName'];
    $path = $args['path'];
    
    $config = $this->get('config');
    $modulesPath = $config['paths']['modules'];
    $filePath = $modulesPath . '/' . $moduleName . '/assets/' . $path;
    
    // Sicherheitscheck: Verhindere Directory Traversal
    $realPath = realpath($filePath);
    $realModulesPath = realpath($modulesPath);
    
    if (!$realPath || !$realModulesPath || strpos($realPath, $realModulesPath) !== 0) {
        $response->getBody()->write('403 - Zugriff verweigert');
        return $response->withStatus(403);
    }
    
    if (!file_exists($filePath) || !is_file($filePath)) {
        $response->getBody()->write('404 - Asset nicht gefunden');
        return $response->withStatus(404);
    }
    
    // Content-Type ermitteln
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    $contentTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];
    
    $contentType = $contentTypes[$extension] ?? 'application/octet-stream';
    
    $response->getBody()->write(file_get_contents($filePath));
    return $response->withHeader('Content-Type', $contentType);
});

// Routing
$app->get('/', function (Request $request, Response $response) {
    $contentManager = $this->get(ContentManager::class);
    $themeManager = $this->get(ThemeManager::class);
    $blockRegistry = $this->get(BlockRegistry::class);
    $config = $this->get('config');

    // Hole Homepage (erste veröffentlichte Seite)
    $pages = $contentManager->getPublishedPages();
    $page = !empty($pages) ? $pages[0] : null;

    // Navigation laden
    $navigation = $contentManager->getNavigationPages('main');
    $footerNavigation = $contentManager->getNavigationPages('footer');

    // Rendere Blöcke
    if ($page && isset($page['sections'])) {
        foreach ($page['sections'] as &$section) {
            if (isset($section['type'])) {
                // Block-Editor Format: { type, data }
                $blockData = $section['data'] ?? [];

                // Rendere Block basierend auf Typ
                $section['html'] = $blockRegistry->renderBlock(
                    $section['type'],
                    $blockData,
                    '' // content ist jetzt in data
                );
            }
        }
    }

    // Rendere Page-Template
    $pageContent = $themeManager->render('page', [
        'page' => $page,
        'siteName' => $config['site']['name'],
    ]);

    // Rendere Layout
    $html = $themeManager->render('layout', [
        'content' => $pageContent,
        'page' => $page,
        'navigation' => $navigation,
        'footerNavigation' => $footerNavigation,
        'siteName' => $config['site']['name'],
    ]);

    $response->getBody()->write($html);
    return $response;
});

$app->get('/{slug}', function (Request $request, Response $response, array $args) {
    $slug = $args['slug'];

    $contentManager = $this->get(ContentManager::class);
    $themeManager = $this->get(ThemeManager::class);
    $blockRegistry = $this->get(BlockRegistry::class);
    $config = $this->get('config');

    // Hole Seite
    $page = $contentManager->getPage($slug);

    if (!$page || $page['status'] !== 'published') {
        $response->getBody()->write('404 - Seite nicht gefunden');
        return $response->withStatus(404);
    }

    // Navigation laden
    $navigation = $contentManager->getNavigationPages('main');
    $footerNavigation = $contentManager->getNavigationPages('footer');

    // Rendere Blöcke
    if (isset($page['sections'])) {
        foreach ($page['sections'] as &$section) {
            if (isset($section['type'])) {
                // Block-Editor Format: { type, data }
                $blockData = $section['data'] ?? [];

                // Rendere Block basierend auf Typ
                $section['html'] = $blockRegistry->renderBlock(
                    $section['type'],
                    $blockData,
                    '' // content ist jetzt in data
                );
            }
        }
    }

    // Rendere Page-Template
    $pageContent = $themeManager->render('page', [
        'page' => $page,
        'siteName' => $config['site']['name'],
    ]);

    // Rendere Layout
    $html = $themeManager->render('layout', [
        'content' => $pageContent,
        'page' => $page,
        'navigation' => $navigation,
        'footerNavigation' => $footerNavigation,
        'siteName' => $config['site']['name'],
    ]);

    $response->getBody()->write($html);
    return $response;
});

// Error Handler
$errorMiddleware = $app->addErrorMiddleware(
    $config['debug']['enabled'],
    true,
    true
);

// App ausführen
$app->run();
