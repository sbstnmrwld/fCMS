<?php

declare(strict_types=1);

namespace FCMS\Controllers;

use FCMS\Core\ContentManager;
use FCMS\Core\AdminAssetManager;
use FCMS\Core\LanguageManager;
use FCMS\Core\AuthManager;
use FCMS\Core\CsrfManager;
use FCMS\Core\Validator;
use FCMS\Exceptions\ValidationException;
use FCMS\Exceptions\NotFoundException;
use FCMS\Exceptions\StorageException;
use FCMS\Blocks\BlockRegistry;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class PageController
{
    private ContentManager $contentManager;
    private AdminAssetManager $adminAssets;
    private LanguageManager $lang;
    private AuthManager $auth;
    private CsrfManager $csrf;
    private BlockRegistry $blockRegistry;
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->contentManager = $container->get(ContentManager::class);
        $this->adminAssets = $container->get(AdminAssetManager::class);
        $this->lang = $container->get(LanguageManager::class);
        $this->auth = $container->get(AuthManager::class);
        $this->csrf = $container->get(CsrfManager::class);
        $this->blockRegistry = $container->get(BlockRegistry::class);
    }

    /**
     * Zeigt alle Seiten an
     */
    public function index(Request $request, Response $response): Response
    {
        $allPages = $this->contentManager->getAllPages();
        
        // Render mit existierender renderAdminTemplate-Funktion
        $html = $this->renderPagesIndex($allPages);
        
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Zeigt Formular für neue Seite
     */
    public function create(Request $request, Response $response): Response
    {
        $html = $this->renderPageForm();
        
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Speichert neue Seite
     */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // CSRF-Validierung
        if (!$this->csrf->validateRequest($data)) {
            return $response->withStatus(403);
        }

        try {
            // Parse Blocks JSON mit Validierung
            $sections = [];
            if (!empty($data['blocks_json'])) {
                $sections = Validator::json($data['blocks_json']);
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

            // Seite erstellen - Validierung erfolgt in ContentManager
            $pageData = [
                'title' => $data['title'] ?? 'Neue Seite',
                'slug' => $data['slug'] ?? '',
                'status' => $data['status'] ?? 'draft',
                'sections' => $sections,
                'nav_main' => $data['nav_main'] ?? false,
                'nav_footer' => $data['nav_footer'] ?? false,
                'nav_order' => $data['nav_order'] ?? 0,
                'nav_label' => $data['nav_label'] ?? '',
                'meta_description' => $data['meta_description'] ?? '',
                'meta_keywords' => $data['meta_keywords'] ?? '',
            ];

            $this->contentManager->createPage($pageData);
            return $response
                ->withHeader('Location', '/admin/pages')
                ->withStatus(302);
        } catch (ValidationException $e) {
            return $response->withStatus(400);
        } catch (StorageException $e) {
            return $response->withStatus(500);
        }
    }

    /**
     * Zeigt Formular zum Bearbeiten
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        $slug = $args['slug'];

        try {
            $page = $this->contentManager->getPage($slug);

            if (!$page) {
                $response->getBody()->write('Seite nicht gefunden');
                return $response->withStatus(404);
            }
        } catch (ValidationException $e) {
            $response->getBody()->write('Ungültiger Slug: ' . $e->getMessage());
            return $response->withStatus(400);
        } catch (StorageException $e) {
            $response->getBody()->write('Fehler beim Laden: ' . $e->getMessage());
            return $response->withStatus(500);
        }

        // Nutze die gemeinsame renderPageForm Methode
        $html = $this->renderPageForm($page);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Aktualisiert Seite
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $slug = $args['slug'];
        $data = $request->getParsedBody();

        if (!$this->csrf->validateRequest($data)) {
            return $response->withStatus(403);
        }

        try {
            // Parse Blocks JSON mit Validierung
            $sections = [];
            if (!empty($data['blocks_json'])) {
                $sections = Validator::json($data['blocks_json']);
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

            // Seite aktualisieren - Validierung erfolgt in ContentManager
            $pageData = [
                'title' => $data['title'] ?? 'Neue Seite',
                'status' => $data['status'] ?? 'draft',
                'sections' => $sections,
                'nav_main' => $data['nav_main'] ?? false,
                'nav_footer' => $data['nav_footer'] ?? false,
                'nav_order' => $data['nav_order'] ?? 0,
                'nav_label' => $data['nav_label'] ?? '',
                'meta_description' => $data['meta_description'] ?? '',
                'meta_keywords' => $data['meta_keywords'] ?? '',
            ];

            $this->contentManager->updatePage($slug, $pageData);
            return $response
                ->withHeader('Location', '/admin/pages')
                ->withStatus(302);
        } catch (ValidationException $_e) {
            return $response->withStatus(400);
        } catch (NotFoundException $_e) {
            return $response->withStatus(404);
        } catch (StorageException $_e) {
            return $response->withStatus(500);
        }
    }

    /**
     * Löscht Seite
     */
    public function delete(Request $_request, Response $response, array $args): Response
    {
        $slug = $args['slug'];

        try {
            $this->contentManager->deletePage($slug);
            return $response
                ->withHeader('Location', '/admin/pages')
                ->withStatus(302);
        } catch (NotFoundException $_e) {
            return $response->withStatus(404);
        } catch (StorageException $_e) {
            return $response->withStatus(500);
        }
    }

    // Helper-Methoden für Rendering (nutzen bestehende Funktion)
    private function renderPagesIndex(array $pages): string
    {
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

        if (empty($pages)) {
            $pagesTable .= '<tr><td colspan="5" class="text-center text-muted">Noch keine Seiten vorhanden</td></tr>';
        } else {
            foreach ($pages as $page) {
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

        return renderAdminTemplate('layout', [
            'content' => $pagesContent,
            'title' => 'Seiten',
            'activeMenu' => 'pages',
            'adminAssets' => $this->adminAssets,
            'lang' => $this->lang,
            'username' => $this->auth->getUsername(),
            'csrfToken' => $this->csrf->getToken(),
        ], $this->container);
    }

    private function renderPageForm(?array $page = null): string
    {
        $isEdit = $page !== null;
        $title = $isEdit ? 'Seite bearbeiten' : 'Neue Seite erstellen';
        $action = $isEdit ? '/admin/pages/update/' . urlencode($page['slug']) : '/admin/pages/create';

        // Prepare blocks JSON
        $blocksJson = $isEdit ? json_encode($page['sections'] ?? []) : '[]';

        // Get available block types
        $availableBlocks = $this->blockRegistry->getAllMetadata();
        $availableBlocksJson = json_encode($availableBlocks);

        // Form values with defaults
        $pageTitle = $isEdit ? htmlspecialchars($page['title']) : '';
        $pageSlug = $isEdit ? htmlspecialchars($page['slug']) : '';
        $pageStatus = $isEdit ? $page['status'] : 'draft';
        $navLabel = $isEdit ? htmlspecialchars($page['navigation']['label'] ?? '') : '';
        $navMain = $isEdit ? ($page['navigation']['main'] ?? false) : false;
        $navFooter = $isEdit ? ($page['navigation']['footer'] ?? false) : false;
        $navOrder = $isEdit ? ($page['navigation']['order'] ?? 0) : 0;
        $metaDescription = $isEdit ? htmlspecialchars($page['meta']['description'] ?? '') : '';
        $metaKeywords = $isEdit ? htmlspecialchars($page['meta']['keywords'] ?? '') : '';

        $formContent = '
        <link rel="stylesheet" href="' . $this->adminAssets->css('block-editor.css') . '">
        <input type="hidden" id="initial_blocks_data" value=\'' . htmlspecialchars($blocksJson, ENT_QUOTES) . '\'>
        <input type="hidden" id="available_blocks_data" value=\'' . htmlspecialchars($availableBlocksJson, ENT_QUOTES) . '\'>

        <div class="container-fluid mt-4">
            <h2><i class="bi bi-' . ($isEdit ? 'pencil-square' : 'plus-circle') . ' me-2"></i>' . $title . '</h2>

            <form method="POST" action="' . $action . '" class="mt-4" id="page-form">
                ' . $this->csrf->getTokenField() . '

                <div class="row">
                    <div class="col-lg-8">
                        <div class="mb-3">
                            <label for="title" class="form-label"><i class="bi bi-type me-1"></i>Titel *</label>
                            <input type="text" class="form-control" id="title" name="title" value="' . $pageTitle . '" required>
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label"><i class="bi bi-link-45deg me-1"></i>URL-Slug</label>
                            ' . ($isEdit
                                ? '<input type="text" class="form-control" id="slug" name="slug" value="' . $pageSlug . '" readonly>
                                   <small class="form-text text-muted">Der Slug kann nicht geändert werden</small>'
                                : '<input type="text" class="form-control" id="slug" name="slug" value="' . $pageSlug . '" placeholder="wird-automatisch-generiert">
                                   <small class="form-text text-muted">Wird automatisch aus dem Titel generiert, wenn leer gelassen</small>'
                            ) . '
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
                                        <option value="draft"' . ($pageStatus === 'draft' ? ' selected' : '') . '>Entwurf</option>
                                        <option value="published"' . ($pageStatus === 'published' ? ' selected' : '') . '>Veröffentlicht</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header"><strong><i class="bi bi-list-ul me-1"></i>Navigation</strong></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="nav_label" class="form-label"><i class="bi bi-tag me-1"></i>Menü-Eintrag</label>
                                    <input type="text" class="form-control" id="nav_label" name="nav_label" value="' . $navLabel . '" placeholder="Leer lassen um Seitentitel zu verwenden">
                                    <small class="form-text text-muted">Wird in Navigationsmenüs angezeigt</small>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="nav_main" name="nav_main" value="1"' . ($navMain ? ' checked' : '') . '>
                                    <label class="form-check-label" for="nav_main">
                                        <i class="bi bi-menu-button-wide me-1"></i>Hauptnavigation
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="nav_footer" name="nav_footer" value="1"' . ($navFooter ? ' checked' : '') . '>
                                    <label class="form-check-label" for="nav_footer">
                                        <i class="bi bi-menu-down me-1"></i>Footer-Navigation
                                    </label>
                                </div>
                                <div class="mt-3">
                                    <label for="nav_order" class="form-label"><i class="bi bi-arrow-down-up me-1"></i>Reihenfolge</label>
                                    <input type="number" class="form-control" id="nav_order" name="nav_order" value="' . $navOrder . '" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header"><strong><i class="bi bi-search me-1"></i>SEO</strong></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="meta_description" class="form-label"><i class="bi bi-text-paragraph me-1"></i>Meta Description</label>
                                    <textarea class="form-control" id="meta_description" name="meta_description" rows="3">' . $metaDescription . '</textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="meta_keywords" class="form-label"><i class="bi bi-tags me-1"></i>Keywords</label>
                                    <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="' . $metaKeywords . '" placeholder="keyword1, keyword2">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>' . ($isEdit ? 'Änderungen speichern' : 'Seite erstellen') . '
                    </button>
                    <a href="/admin/pages" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Abbrechen
                    </a>
                </div>
            </form>
        </div>

        <script src="' . $this->adminAssets->js('block-editor.js') . '"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                blockEditor = new BlockEditor("block-editor-container");
            });
        </script>';

        return renderAdminTemplate('layout', [
            'content' => $formContent,
            'title' => $title,
            'activeMenu' => 'pages',
            'adminAssets' => $this->adminAssets,
            'lang' => $this->lang,
            'username' => $this->auth->getUsername(),
            'csrfToken' => $this->csrf->getToken(),
        ], $this->container);
    }
}
