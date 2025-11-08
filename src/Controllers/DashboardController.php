<?php

declare(strict_types=1);

namespace FCMS\Controllers;

use FCMS\Core\ContentManager;
use FCMS\Core\AdminAssetManager;
use FCMS\Core\LanguageManager;
use FCMS\Core\AuthManager;
use FCMS\Core\CsrfManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class DashboardController
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
     * Zeigt Dashboard mit Statistiken
     */
    public function index(Request $request, Response $response): Response
    {
        $allPages = $this->contentManager->getAllPages();
        $publishedPages = $this->contentManager->getPublishedPages();

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
            'title' => $this->lang->t('admin.dashboard'),
            'activeMenu' => 'dashboard',
            'adminAssets' => $this->adminAssets,
            'lang' => $this->lang,
            'username' => $this->auth->getUsername(),
            'csrfToken' => $this->csrf->getToken(),
        ], $this->container);

        $response->getBody()->write($html);
        return $response;
    }
}
