<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Controllers;

use FCMS\Controllers\NavigationController;
use FCMS\Core\ContentManager;
use FCMS\Core\AdminAssetManager;
use FCMS\Core\LanguageManager;
use FCMS\Core\AuthManager;
use FCMS\Core\CsrfManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class NavigationControllerTest extends TestCase
{
    private NavigationController $controller;
    private ContainerInterface $container;
    private ContentManager $contentManager;
    private AdminAssetManager $adminAssets;
    private LanguageManager $lang;
    private AuthManager $authManager;
    private CsrfManager $csrfManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->contentManager = $this->createMock(ContentManager::class);
        $this->adminAssets = $this->createMock(AdminAssetManager::class);
        $this->lang = $this->createMock(LanguageManager::class);
        $this->authManager = $this->createMock(AuthManager::class);
        $this->csrfManager = $this->createMock(CsrfManager::class);

        // Mock container
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('get')->willReturnCallback(function ($id) {
            return match ($id) {
                ContentManager::class => $this->contentManager,
                AdminAssetManager::class => $this->adminAssets,
                LanguageManager::class => $this->lang,
                AuthManager::class => $this->authManager,
                CsrfManager::class => $this->csrfManager,
                default => null,
            };
        });

        // Setup default mock behaviors
        $this->adminAssets->method('css')->willReturn('/admin/assets/css/test.css');
        $this->adminAssets->method('js')->willReturn('/admin/assets/js/test.js');
        $this->authManager->method('getUsername')->willReturn('testuser');
        $this->csrfManager->method('getToken')->willReturn('test-token');
        $this->csrfManager->method('getTokenField')->willReturn('<input type="hidden" name="csrf_token" value="test">');

        $this->controller = new NavigationController($this->container);
    }

    public function testIndexRendersNavigationLists(): void
    {
        $mainNavPages = [
            [
                'title' => 'Home',
                'slug' => 'home',
                'status' => 'published',
                'navigation' => ['label' => 'Startseite', 'main' => true, 'footer' => false, 'order' => 0]
            ],
        ];

        $footerNavPages = [
            [
                'title' => 'Impressum',
                'slug' => 'impressum',
                'status' => 'published',
                'navigation' => ['label' => '', 'main' => false, 'footer' => true, 'order' => 0]
            ],
        ];

        $this->contentManager->method('getNavigationPages')
            ->willReturnCallback(function ($type) use ($mainNavPages, $footerNavPages) {
                return $type === 'main' ? $mainNavPages : $footerNavPages;
            });

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $response->method('getBody')->willReturn($body);
        $body->expects($this->once())
            ->method('write')
            ->with($this->isType('string'));

        $result = $this->controller->index($request, $response);

        $this->assertSame($response, $result);
    }

    public function testUpdateWithValidDataReturnsSuccess(): void
    {
        $this->csrfManager->method('validateRequest')->willReturn(true);

        $allPages = [
            ['slug' => 'home', 'navigation' => ['main' => true, 'footer' => false]],
            ['slug' => 'about', 'navigation' => ['main' => true, 'footer' => false]],
            ['slug' => 'impressum', 'navigation' => ['main' => false, 'footer' => true]],
        ];

        $this->contentManager->method('getAllPages')->willReturn($allPages);
        $this->contentManager->expects($this->exactly(3))->method('updatePage');

        $jsonBody = json_encode([
            'main' => ['home', 'about'],
            'footer' => ['impressum'],
            'csrf_token' => 'valid-token'
        ]);

        $bodyStream = $this->createMock(StreamInterface::class);
        $bodyStream->method('__toString')->willReturn($jsonBody);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn($bodyStream);

        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $response->method('getBody')->willReturn($responseBody);
        $response->method('withHeader')->willReturnSelf();

        $responseBody->expects($this->once())
            ->method('write')
            ->with($this->stringContains('"success":true'));

        $result = $this->controller->update($request, $response);

        $this->assertSame($response, $result);
    }

    public function testUpdateWithInvalidCsrfReturns403(): void
    {
        $this->csrfManager->method('validateRequest')->willReturn(false);

        $jsonBody = json_encode([
            'main' => [],
            'footer' => [],
            'csrf_token' => 'invalid-token'
        ]);

        $bodyStream = $this->createMock(StreamInterface::class);
        $bodyStream->method('__toString')->willReturn($jsonBody);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn($bodyStream);

        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $response->method('getBody')->willReturn($body);
        $response->method('withHeader')->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(403)
            ->willReturnSelf();

        $body->expects($this->once())
            ->method('write')
            ->with($this->stringContains('"success":false'));

        $result = $this->controller->update($request, $response);

        $this->assertSame($response, $result);
    }

    public function testUpdateWithInvalidDataReturns400(): void
    {
        $this->csrfManager->method('validateRequest')->willReturn(true);

        $jsonBody = json_encode([
            'main' => 'not-an-array', // Invalid: should be array
            'footer' => [],
            'csrf_token' => 'valid-token'
        ]);

        $bodyStream = $this->createMock(StreamInterface::class);
        $bodyStream->method('__toString')->willReturn($jsonBody);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn($bodyStream);

        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $response->method('getBody')->willReturn($body);
        $response->method('withHeader')->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(400)
            ->willReturnSelf();

        $body->expects($this->once())
            ->method('write')
            ->with($this->stringContains('"success":false'));

        $result = $this->controller->update($request, $response);

        $this->assertSame($response, $result);
    }

    public function testUpdateMovesPageBetweenNavigations(): void
    {
        $this->csrfManager->method('validateRequest')->willReturn(true);

        $allPages = [
            ['slug' => 'home', 'navigation' => ['main' => true, 'footer' => false]],
            ['slug' => 'about', 'navigation' => ['main' => true, 'footer' => false]],
        ];

        $this->contentManager->method('getAllPages')->willReturn($allPages);

        // Expect 'home' to move to footer
        $this->contentManager->expects($this->exactly(2))
            ->method('updatePage')
            ->withConsecutive(
                [$this->equalTo('home'), $this->callback(function ($data) {
                    return $data['nav_main'] === false && $data['nav_footer'] === true;
                })],
                [$this->equalTo('about'), $this->callback(function ($data) {
                    return $data['nav_main'] === true && $data['nav_footer'] === false;
                })]
            );

        $jsonBody = json_encode([
            'main' => ['about'],
            'footer' => ['home'],
            'csrf_token' => 'valid-token'
        ]);

        $bodyStream = $this->createMock(StreamInterface::class);
        $bodyStream->method('__toString')->willReturn($jsonBody);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn($bodyStream);

        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $response->method('getBody')->willReturn($body);
        $response->method('withHeader')->willReturnSelf();

        $this->controller->update($request, $response);
    }

    public function testUpdateReordersPages(): void
    {
        $this->csrfManager->method('validateRequest')->willReturn(true);

        $allPages = [
            ['slug' => 'first', 'navigation' => ['main' => true, 'footer' => false]],
            ['slug' => 'second', 'navigation' => ['main' => true, 'footer' => false]],
            ['slug' => 'third', 'navigation' => ['main' => true, 'footer' => false]],
        ];

        $this->contentManager->method('getAllPages')->willReturn($allPages);

        // Expect pages in new order: second (0), third (1), first (2)
        $this->contentManager->expects($this->exactly(3))
            ->method('updatePage')
            ->withConsecutive(
                [$this->equalTo('first'), $this->callback(function ($data) {
                    return $data['nav_order'] === 2; // Now at position 2
                })],
                [$this->equalTo('second'), $this->callback(function ($data) {
                    return $data['nav_order'] === 0; // Now at position 0
                })],
                [$this->equalTo('third'), $this->callback(function ($data) {
                    return $data['nav_order'] === 1; // Now at position 1
                })]
            );

        $jsonBody = json_encode([
            'main' => ['second', 'third', 'first'], // Reordered
            'footer' => [],
            'csrf_token' => 'valid-token'
        ]);

        $bodyStream = $this->createMock(StreamInterface::class);
        $bodyStream->method('__toString')->willReturn($jsonBody);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn($bodyStream);

        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $response->method('getBody')->willReturn($body);
        $response->method('withHeader')->willReturnSelf();

        $this->controller->update($request, $response);
    }
}
