<?php

declare(strict_types=1);

namespace FCMS\Controllers;

use FCMS\Blocks\BlockRegistry;
use FCMS\Core\AdminAssetManager;
use FCMS\Core\LanguageManager;
use FCMS\Core\AuthManager;
use FCMS\Core\CsrfManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class BlockController
{
    private BlockRegistry $blockRegistry;
    private AdminAssetManager $adminAssets;
    private LanguageManager $lang;
    private AuthManager $auth;
    private CsrfManager $csrf;
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->blockRegistry = $container->get(BlockRegistry::class);
        $this->adminAssets = $container->get(AdminAssetManager::class);
        $this->lang = $container->get(LanguageManager::class);
        $this->auth = $container->get(AuthManager::class);
        $this->csrf = $container->get(CsrfManager::class);
    }

    /**
     * Zeigt alle Blocks
     */
    public function index(Request $request, Response $response): Response
    {
        $blocks = $this->blockRegistry->getAllBlocks();
        $blocksMetadata = $this->blockRegistry->getAllMetadata();

        $blocksContent = '
    <div class="container-fluid mt-4">
        <div class="page-header">
            <h1><i class="bi bi-boxes me-2"></i>Block-Bibliothek</h1>
            <p>Übersicht aller verfügbaren Content-Blöcke (' . count($blocks) . ' Blöcke)</p>
        </div>

        <div class="row">';

        // Dynamisch alle registrierten Blöcke anzeigen
        foreach ($blocksMetadata as $blockType => $metadata) {
            $iconClass = $metadata['icon'] ?? 'bi-puzzle';
            $blockName = $metadata['name'] ?? ucfirst($blockType);
            $description = $metadata['description'] ?? 'Keine Beschreibung verfügbar';
            $category = $metadata['category'] ?? 'other';

            // Hole den Block für Beispiel-Rendering
            $block = $this->blockRegistry->get($blockType);
            $exampleHtml = '';

            // Versuche ein Beispiel zu rendern
            if ($block) {
                try {
                    $defaultAttrs = $block->getDefaultAttributes();
                    // Setze Beispiel-Daten für verschiedene Block-Typen (außer Module-Blocks)
                    switch ($blockType) {
                        case 'paragraph':
                            $defaultAttrs['text'] = 'Dies ist ein Beispiel-Absatz. Der Paragraph-Block wird für normalen Fließtext verwendet.';
                            break;
                        case 'heading':
                            $defaultAttrs['text'] = 'Beispiel-Überschrift';
                            $defaultAttrs['level'] = 2;
                            break;
                        case 'quote':
                            $defaultAttrs['text'] = 'Ein inspirierendes Zitat als Beispiel.';
                            $defaultAttrs['author'] = 'Autor Name';
                            break;
                        case 'list':
                            $defaultAttrs['items'] = ['Punkt 1', 'Punkt 2', 'Punkt 3'];
                            $defaultAttrs['ordered'] = false;
                            break;
                        case 'image':
                            $defaultAttrs['url'] = 'https://via.placeholder.com/400x200?text=Beispielbild';
                            $defaultAttrs['alt'] = 'Beispielbild';
                            $defaultAttrs['caption'] = 'Eine Bildunterschrift';
                            break;
                        case 'button':
                            $defaultAttrs['text'] = 'Beispiel-Button';
                            $defaultAttrs['url'] = '#';
                            $defaultAttrs['style'] = 'primary';
                            break;
                        // Module-Blöcke (z.B. form) rendern sich selbst mit leeren Attributen
                        default:
                            break;
                    }

                    $exampleHtml = $block->render($defaultAttrs, '');
                } catch (\Exception $e) {
                    $exampleHtml = '<div class="alert alert-warning">Beispiel konnte nicht geladen werden</div>';
                }
            }

            $blocksContent .= '
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi ' . htmlspecialchars($iconClass) . ' text-hellblau me-2"></i>
                            ' . htmlspecialchars($blockName) . '
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3"><small>' . htmlspecialchars($description) . '</small></p>

                        <div class="mb-3">
                            <span class="badge bg-secondary">' . htmlspecialchars($blockType) . '</span>
                            <span class="badge bg-info">' . htmlspecialchars($category) . '</span>
                        </div>';

            if (!empty($exampleHtml)) {
                $blocksContent .= '
                        <h6 class="fw-bold mb-2">Beispiel:</h6>
                        <div class="border rounded p-3 bg-light mb-3">
                            ' . $exampleHtml . '
                        </div>';
            }

            $blocksContent .= '
                    </div>
                </div>
            </div>';
        }

        $blocksContent .= '
        </div>

        <div class="mt-4">
            <div class="alert alert-info">
                <i class="bi bi-lightbulb me-2"></i>
                <strong>Tipp:</strong> Blöcke werden im Page-Editor verwendet und können beliebig kombiniert werden.
                Insgesamt sind <strong>' . count($blocks) . ' Blöcke</strong> verfügbar.
            </div>

            <a href="/admin" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Zurück zum Dashboard
            </a>
        </div>
    </div>';

        $html = renderAdminTemplate('layout', [
            'content' => $blocksContent,
            'title' => 'Block-Bibliothek',
            'activeMenu' => 'blocks',
            'adminAssets' => $this->adminAssets,
            'lang' => $this->lang,
            'username' => $this->auth->getUsername(),
            'csrfToken' => $this->csrf->getToken(),
        ], $this->container);

        $response->getBody()->write($html);
        return $response;
    }
}
