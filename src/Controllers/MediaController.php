<?php

declare(strict_types=1);

namespace FCMS\Controllers;

use FCMS\Core\AdminAssetManager;
use FCMS\Core\LanguageManager;
use FCMS\Core\AuthManager;
use FCMS\Core\CsrfManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class MediaController
{
    private AdminAssetManager $adminAssets;
    private LanguageManager $lang;
    private AuthManager $auth;
    private CsrfManager $csrf;
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->adminAssets = $container->get(AdminAssetManager::class);
        $this->lang = $container->get(LanguageManager::class);
        $this->auth = $container->get(AuthManager::class);
        $this->csrf = $container->get(CsrfManager::class);
    }

    /**
     * Zeigt Media-Library
     */
    public function index(Request $request, Response $response): Response
    {
        $placeholderContent = '<div class="container mt-4">
            <h2><i class="bi bi-image me-2"></i>Media-Verwaltung</h2>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Die Media-Verwaltung ist in Entwicklung.
            </div>
        </div>';

        $html = renderAdminTemplate('layout', [
            'content' => $placeholderContent,
            'title' => 'Media',
            'activeMenu' => 'media',
            'adminAssets' => $this->adminAssets,
            'lang' => $this->lang,
            'username' => $this->auth->getUsername(),
            'csrfToken' => $this->csrf->getToken(),
        ], $this->container);

        $response->getBody()->write($html);
        return $response;
    }
}
