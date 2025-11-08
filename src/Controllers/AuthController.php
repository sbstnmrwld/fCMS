<?php

declare(strict_types=1);

namespace FCMS\Controllers;

use FCMS\Core\AuthManager;
use FCMS\Core\CsrfManager;
use FCMS\Core\AdminAssetManager;
use FCMS\Core\LanguageManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class AuthController
{
    private AuthManager $auth;
    private CsrfManager $csrf;
    private AdminAssetManager $adminAssets;
    private LanguageManager $lang;
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->auth = $container->get(AuthManager::class);
        $this->csrf = $container->get(CsrfManager::class);
        $this->adminAssets = $container->get(AdminAssetManager::class);
        $this->lang = $container->get(LanguageManager::class);
    }

    public function showLogin(Request $request, Response $response): Response
    {
        if ($this->auth->isAuthenticated()) {
            return $response->withHeader('Location', '/admin')->withStatus(302);
        }

        $config = $this->container->get('config');

        $html = renderAdminTemplate('login', [
            'csrfField' => $this->csrf->getTokenField(),
            'adminAssets' => $this->adminAssets,
            'lang' => $this->lang,
            'config' => $config,
            'lockoutTime' => $this->auth->getLockoutTimeRemaining(),
        ], $this->container);

        $response->getBody()->write($html);
        return $response;
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (!$this->csrf->validateRequest($data)) {
            return $response->withStatus(403);
        }

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        try {
            if ($this->auth->attempt($username, $password)) {
                return $response->withHeader('Location', '/admin')->withStatus(302);
            }
        } catch (\Exception $e) {
            // Validation error
        }

        return $response->withHeader('Location', '/admin/login')->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {
        $this->auth->logout();
        return $response->withHeader('Location', '/admin/login')->withStatus(302);
    }
}
