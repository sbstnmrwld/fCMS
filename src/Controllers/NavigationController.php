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
     * Zeigt Navigation-Verwaltung
     */
    public function index(Request $request, Response $response): Response
    {
        $allPages = $this->contentManager->getAllPages();
        $mainNavPages = $this->contentManager->getNavigationPages('main');
        $footerNavPages = $this->contentManager->getNavigationPages('footer');

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
        $availablePages = array_filter($allPages, function ($page) {
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
            'adminAssets' => $this->adminAssets,
            'lang' => $this->lang,
            'username' => $this->auth->getUsername(),
            'csrfToken' => $this->csrf->getToken(),
        ], $this->container);

        $response->getBody()->write($html);
        return $response;
    }
}
