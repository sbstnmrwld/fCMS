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

class ThemeController
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
     * Zeigt Theme-Verwaltung
     */
    public function index(Request $request, Response $response): Response
    {
        $themesContent = '<div class="container mt-4">
            <h2><i class="bi bi-palette me-2"></i>Themes</h2>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Theme-Verwaltung ist in Entwicklung.
            </div>
        </div>';

        $html = renderAdminTemplate('layout', [
            'content' => $themesContent,
            'title' => 'Themes',
            'activeMenu' => 'themes',
            'adminAssets' => $this->adminAssets,
            'lang' => $this->lang,
            'username' => $this->auth->getUsername(),
            'csrfToken' => $this->csrf->getToken(),
        ], $this->container);

        $response->getBody()->write($html);
        return $response;
    }
}
