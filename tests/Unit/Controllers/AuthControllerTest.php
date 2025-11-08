<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Controllers;

use FCMS\Controllers\AuthController;
use FCMS\Core\AuthManager;
use FCMS\Core\CsrfManager;
use FCMS\Core\AdminAssetManager;
use FCMS\Core\LanguageManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class AuthControllerTest extends TestCase
{
    private AuthController $controller;
    private ContainerInterface $container;
    private AuthManager $authManager;
    private CsrfManager $csrfManager;
    private AdminAssetManager $adminAssets;
    private LanguageManager $lang;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->authManager = $this->createMock(AuthManager::class);
        $this->csrfManager = $this->createMock(CsrfManager::class);
        $this->adminAssets = $this->createMock(AdminAssetManager::class);
        $this->lang = $this->createMock(LanguageManager::class);

        // Mock container
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('get')->willReturnCallback(function ($id) {
            return match ($id) {
                AuthManager::class => $this->authManager,
                CsrfManager::class => $this->csrfManager,
                AdminAssetManager::class => $this->adminAssets,
                LanguageManager::class => $this->lang,
                'config' => ['privacy' => ['admin_session_notice' => true]],
                default => null,
            };
        });

        $this->controller = new AuthController($this->container);
    }

    public function testShowLoginRendersLoginPageWhenNotAuthenticated(): void
    {
        $this->authManager->method('isAuthenticated')->willReturn(false);
        $this->authManager->method('getLockoutTimeRemaining')->willReturn(0);
        $this->csrfManager->method('getTokenField')->willReturn('<input type="hidden" name="csrf_token" value="test">');

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $response->method('getBody')->willReturn($body);
        $body->expects($this->once())->method('write')->with($this->isType('string'));

        $result = $this->controller->showLogin($request, $response);

        $this->assertSame($response, $result);
    }

    public function testShowLoginRedirectsWhenAlreadyAuthenticated(): void
    {
        $this->authManager->method('isAuthenticated')->willReturn(true);

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Location', '/admin')
            ->willReturnSelf();

        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturnSelf();

        $result = $this->controller->showLogin($request, $response);

        $this->assertSame($response, $result);
    }

    public function testLoginSuccessRedirectsToAdmin(): void
    {
        $this->csrfManager->method('validateRequest')->willReturn(true);
        $this->authManager->method('attempt')->willReturn(true);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'username' => 'admin',
            'password' => 'password',
            'csrf_token' => 'valid-token'
        ]);

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Location', '/admin')
            ->willReturnSelf();

        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturnSelf();

        $result = $this->controller->login($request, $response);

        $this->assertSame($response, $result);
    }

    public function testLoginFailureRedirectsToLoginPage(): void
    {
        $this->csrfManager->method('validateRequest')->willReturn(true);
        $this->authManager->method('attempt')->willReturn(false);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'username' => 'admin',
            'password' => 'wrong-password',
            'csrf_token' => 'valid-token'
        ]);

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Location', '/admin/login')
            ->willReturnSelf();

        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturnSelf();

        $result = $this->controller->login($request, $response);

        $this->assertSame($response, $result);
    }

    public function testLoginWithInvalidCsrfReturns403(): void
    {
        $this->csrfManager->method('validateRequest')->willReturn(false);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'username' => 'admin',
            'password' => 'password',
            'csrf_token' => 'invalid-token'
        ]);

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('withStatus')
            ->with(403)
            ->willReturnSelf();

        $result = $this->controller->login($request, $response);

        $this->assertSame($response, $result);
    }

    public function testLogoutRedirectsToLoginPage(): void
    {
        $this->authManager->expects($this->once())->method('logout');

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Location', '/admin/login')
            ->willReturnSelf();

        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturnSelf();

        $result = $this->controller->logout($request, $response);

        $this->assertSame($response, $result);
    }
}
