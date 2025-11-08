<?php

declare(strict_types=1);

namespace FCMS\Controllers;

use FCMS\Core\AdminAssetManager;
use FCMS\Core\ContentManager;
use FCMS\Core\LanguageManager;
use FCMS\Core\AuthManager;
use FCMS\Core\CsrfManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class NavigationController
{
    private ContentManager $contentManager;
    private AdminAssetManager $adminAssets;
    private LanguageManager $lang;
    private AuthManager $auth;
    private CsrfManager $csrf;
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->contentManager = $container->get(ContentManager::class);
        $this->adminAssets = $container->get(AdminAssetManager::class);
        $this->lang = $container->get(LanguageManager::class);
        $this->auth = $container->get(AuthManager::class);
        $this->csrf = $container->get(CsrfManager::class);
    }

    /**
     * Aktualisiert die Navigation per API (für Drag & Drop)
     */
    public function update(Request $request, Response $response): Response
    {
        // Parse JSON body
        $contentType = $request->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $body = (string) $request->getBody();
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Invalid JSON']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        } else {
            $data = $request->getParsedBody();
        }

        // CSRF-Validierung
        if (!$this->csrf->validateRequest($data)) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Invalid CSRF token']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        try {
            // Erwarte Struktur: ['main' => [...slugs], 'footer' => [...slugs]]
            $mainNav = $data['main'] ?? [];
            $footerNav = $data['footer'] ?? [];

            // Validiere dass es Arrays sind
            if (!is_array($mainNav) || !is_array($footerNav)) {
                throw new \FCMS\Exceptions\ValidationException('Invalid navigation data: main=' . gettype($mainNav) . ', footer=' . gettype($footerNav));
            }

            // Aktualisiere alle Seiten
            $this->updateNavigationStructure($mainNav, $footerNav);

            $response->getBody()->write(json_encode(['success' => true]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    /**
     * Aktualisiert die Navigationsstruktur aller Seiten
     */
    private function updateNavigationStructure(array $mainSlugs, array $footerSlugs): void
    {
        $allPages = $this->contentManager->getAllPages();

        foreach ($allPages as $page) {
            $slug = $page['slug'];
            $inMain = in_array($slug, $mainSlugs);
            $inFooter = in_array($slug, $footerSlugs);

            // Bestimme Order basierend auf Position im Array
            $mainOrder = $inMain ? array_search($slug, $mainSlugs) : 0;
            $footerOrder = $inFooter ? array_search($slug, $footerSlugs) : 0;

            // Aktualisiere die Seite
            $this->contentManager->updatePage($slug, [
                'nav_main' => $inMain,
                'nav_footer' => $inFooter,
                'nav_order' => $inMain ? $mainOrder : $footerOrder,
            ]);
        }
    }

    /**
     * Zeigt Navigation-Verwaltung
     */
    public function index(Request $request, Response $response): Response
    {
        $mainNavPages = $this->contentManager->getNavigationPages('main');
        $footerNavPages = $this->contentManager->getNavigationPages('footer');

        // Helper function to render page item
        $renderPageItem = function ($page) {
            $statusBadge = $page['status'] === 'published'
                ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Veröffentlicht</span>'
                : '<span class="badge bg-secondary"><i class="bi bi-pencil-square me-1"></i>Entwurf</span>';

            $label = ($page['navigation']['label'] ?? '') ?: $page['title'];

            return '<div class="navigation-page-item" data-slug="' . htmlspecialchars($page['slug']) . '">
                <div class="navigation-page-item-drag-handle">
                    <i class="bi bi-grip-vertical"></i>
                </div>
                <div class="navigation-page-item-content">
                    <div class="navigation-page-item-title">
                        <strong>' . htmlspecialchars($page['title']) . '</strong>
                        ' . $statusBadge . '
                    </div>
                    <div class="navigation-page-item-meta">
                        <small class="text-muted">
                            Label: <span class="text-dark">' . htmlspecialchars($label) . '</span> |
                            Slug: <code>' . htmlspecialchars($page['slug']) . '</code>
                        </small>
                    </div>
                </div>
                <div class="navigation-page-item-actions">
                    <a href="/admin/pages/edit/' . urlencode($page['slug']) . '" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>
            </div>';
        };

        // Hauptnavigation Liste
        $mainNavItems = '';
        foreach ($mainNavPages as $page) {
            $mainNavItems .= $renderPageItem($page);
        }
        if (empty($mainNavItems)) {
            $mainNavItems = '<div class="text-center text-muted py-4">Ziehen Sie Seiten hierher, um sie zur Hauptnavigation hinzuzufügen</div>';
        }

        // Footer Navigation Liste
        $footerNavItems = '';
        foreach ($footerNavPages as $page) {
            $footerNavItems .= $renderPageItem($page);
        }
        if (empty($footerNavItems)) {
            $footerNavItems = '<div class="text-center text-muted py-4">Ziehen Sie Seiten hierher, um sie zur Footer-Navigation hinzuzufügen</div>';
        }

        $navigationContent = '<link rel="stylesheet" href="' . $this->adminAssets->css('navigation.css') . '">

        <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-list-ul me-2"></i>Navigation verwalten</h2>
                <div>
                    <button id="save-navigation" class="btn btn-primary me-2" style="display:none;">
                        <i class="bi bi-save me-1"></i>Änderungen speichern
                    </button>
                    <a href="/admin/pages/new" class="btn btn-success">
                        <i class="bi bi-plus-circle me-1"></i>Neue Seite erstellen
                    </a>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Hinweis:</strong> Verschieben Sie Seiten per Drag & Drop zwischen den Listen und ordnen Sie sie neu an.
                Alle Seiten müssen in Haupt- oder Footer-Navigation sein - ein Entfernen ist nicht möglich.
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-menu-button-wide me-2"></i>Hauptnavigation</h5>
                        </div>
                        <div class="card-body">
                            <div id="main-nav-list" class="navigation-list" data-nav-type="main">
                                ' . $mainNavItems . '
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-menu-down me-2"></i>Footer-Navigation</h5>
                        </div>
                        <div class="card-body">
                            <div id="footer-nav-list" class="navigation-list" data-nav-type="footer">
                                ' . $footerNavItems . '
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            const CSRF_TOKEN = "' . $this->csrf->getToken() . '";
        </script>
        <script src="' . $this->adminAssets->js('navigation.js') . '"></script>';

        $html = renderAdminTemplate('layout', [
            'content' => $navigationContent,
            'title' => 'Navigation',
            'activeMenu' => 'navigation',
            'adminAssets' => $this->adminAssets,
            'lang' => $this->lang,
            'username' => $this->auth->getUsername(),
            'csrfToken' => $this->csrf->getToken(),
        ], $this->container);

        $response->getBody()->write($html);
        return $response;
    }
}
