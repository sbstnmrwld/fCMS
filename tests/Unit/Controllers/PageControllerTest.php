<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Controllers;

use FCMS\Controllers\PageController;
use FCMS\Core\ContentManager;
use FCMS\Core\AdminAssetManager;
use FCMS\Core\LanguageManager;
use FCMS\Core\AuthManager;
use FCMS\Core\CsrfManager;
use FCMS\Blocks\BlockRegistry;
use FCMS\Exceptions\ValidationException;
use FCMS\Exceptions\NotFoundException;
use FCMS\Exceptions\StorageException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PageControllerTest extends TestCase
{
    private PageController $controller;
    private ContainerInterface $container;
    private ContentManager $contentManager;
    private AdminAssetManager $adminAssets;
    private LanguageManager $lang;
    private AuthManager $authManager;
    private CsrfManager $csrfManager;
    private BlockRegistry $blockRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->contentManager = $this->createMock(ContentManager::class);
        $this->adminAssets = $this->createMock(AdminAssetManager::class);
        $this->lang = $this->createMock(LanguageManager::class);
        $this->authManager = $this->createMock(AuthManager::class);
        $this->csrfManager = $this->createMock(CsrfManager::class);
        $this->blockRegistry = $this->createMock(BlockRegistry::class);

        // Mock container
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('get')->willReturnCallback(function ($id) {
            return match ($id) {
                ContentManager::class => $this->contentManager,
                AdminAssetManager::class => $this->adminAssets,
                LanguageManager::class => $this->lang,
                AuthManager::class => $this->authManager,
                CsrfManager::class => $this->csrfManager,
                BlockRegistry::class => $this->blockRegistry,
                default => null,
            };
        });

        // Setup default mock behaviors
        $this->adminAssets->method('css')->willReturn('/admin/assets/css/test.css');
        $this->adminAssets->method('js')->willReturn('/admin/assets/js/test.js');
        $this->authManager->method('getUsername')->willReturn('testuser');
        $this->csrfManager->method('getToken')->willReturn('test-token');
        $this->csrfManager->method('getTokenField')->willReturn('<input type="hidden" name="csrf_token" value="test">');

        $this->controller = new PageController($this->container);
    }

    public function testIndexRendersPagesList(): void
    {
        $pages = [
            ['title' => 'Test Page 1', 'slug' => 'test-1', 'status' => 'published', 'created' => time()],
            ['title' => 'Test Page 2', 'slug' => 'test-2', 'status' => 'draft', 'created' => time()],
        ];

        $this->contentManager->method('getAllPages')->willReturn($pages);

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

    public function testCreateRendersForm(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $response->method('getBody')->willReturn($body);
        $body->expects($this->once())->method('write')->with($this->isType('string'));

        $result = $this->controller->create($request, $response);

        $this->assertSame($response, $result);
    }

    public function testStoreCreatesPageAndRedirects(): void
    {
        $this->csrfManager->method('validateRequest')->willReturn(true);
        $this->contentManager->expects($this->once())->method('createPage');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'title' => 'New Page',
            'slug' => 'new-page',
            'status' => 'draft',
            'csrf_token' => 'valid-token'
        ]);

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Location', '/admin/pages')
            ->willReturnSelf();

        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturnSelf();

        $result = $this->controller->store($request, $response);

        $this->assertSame($response, $result);
    }

    public function testStoreWithInvalidCsrfReturns403(): void
    {
        $this->csrfManager->method('validateRequest')->willReturn(false);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'title' => 'New Page',
            'csrf_token' => 'invalid-token'
        ]);

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('withStatus')
            ->with(403)
            ->willReturnSelf();

        $result = $this->controller->store($request, $response);

        $this->assertSame($response, $result);
    }

    public function testStoreWithValidationExceptionReturns400(): void
    {
        $this->csrfManager->method('validateRequest')->willReturn(true);
        $this->contentManager->method('createPage')
            ->willThrowException(new ValidationException('Invalid data'));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'title' => 'New Page',
            'csrf_token' => 'valid-token'
        ]);

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('withStatus')
            ->with(400)
            ->willReturnSelf();

        $result = $this->controller->store($request, $response);

        $this->assertSame($response, $result);
    }

    public function testEditRendersEditForm(): void
    {
        $page = [
            'title' => 'Test Page',
            'slug' => 'test-page',
            'status' => 'published',
            'sections' => [],
            'navigation' => ['label' => 'Test', 'main' => true, 'footer' => false, 'order' => 0],
            'meta' => ['description' => 'Test description', 'keywords' => 'test, page']
        ];

        $this->contentManager->method('getPage')->with('test-page')->willReturn($page);
        $this->blockRegistry->method('getAllMetadata')->willReturn([]);

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $response->method('getBody')->willReturn($body);
        $body->expects($this->once())
            ->method('write')
            ->with($this->isType('string'));

        $result = $this->controller->edit($request, $response, ['slug' => 'test-page']);

        $this->assertSame($response, $result);
    }

    public function testEditWithNonExistentPageReturns404(): void
    {
        $this->contentManager->method('getPage')->willReturn(null);

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $response->method('getBody')->willReturn($body);
        $body->expects($this->once())->method('write')->with('Seite nicht gefunden');

        $response->expects($this->once())
            ->method('withStatus')
            ->with(404)
            ->willReturnSelf();

        $result = $this->controller->edit($request, $response, ['slug' => 'nonexistent']);

        $this->assertSame($response, $result);
    }

    public function testUpdateUpdatesPageAndRedirects(): void
    {
        $this->csrfManager->method('validateRequest')->willReturn(true);
        $this->contentManager->expects($this->once())
            ->method('updatePage')
            ->with('test-page', $this->isType('array'));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'title' => 'Updated Page',
            'status' => 'published',
            'csrf_token' => 'valid-token'
        ]);

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Location', '/admin/pages')
            ->willReturnSelf();

        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturnSelf();

        $result = $this->controller->update($request, $response, ['slug' => 'test-page']);

        $this->assertSame($response, $result);
    }

    public function testUpdateWithInvalidCsrfReturns403(): void
    {
        $this->csrfManager->method('validateRequest')->willReturn(false);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'title' => 'Updated Page',
            'csrf_token' => 'invalid-token'
        ]);

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('withStatus')
            ->with(403)
            ->willReturnSelf();

        $result = $this->controller->update($request, $response, ['slug' => 'test-page']);

        $this->assertSame($response, $result);
    }

    public function testUpdateWithNotFoundExceptionReturns404(): void
    {
        $this->csrfManager->method('validateRequest')->willReturn(true);
        $this->contentManager->method('updatePage')
            ->willThrowException(NotFoundException::page('test-page'));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'title' => 'Updated Page',
            'csrf_token' => 'valid-token'
        ]);

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('withStatus')
            ->with(404)
            ->willReturnSelf();

        $result = $this->controller->update($request, $response, ['slug' => 'test-page']);

        $this->assertSame($response, $result);
    }

    public function testDeleteRemovesPageAndRedirects(): void
    {
        $this->contentManager->expects($this->once())
            ->method('deletePage')
            ->with('test-page');

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Location', '/admin/pages')
            ->willReturnSelf();

        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturnSelf();

        $result = $this->controller->delete($request, $response, ['slug' => 'test-page']);

        $this->assertSame($response, $result);
    }

    public function testDeleteWithNotFoundExceptionReturns404(): void
    {
        $this->contentManager->method('deletePage')
            ->willThrowException(NotFoundException::page('test-page'));

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('withStatus')
            ->with(404)
            ->willReturnSelf();

        $result = $this->controller->delete($request, $response, ['slug' => 'test-page']);

        $this->assertSame($response, $result);
    }

    public function testDeleteWithStorageExceptionReturns500(): void
    {
        $this->contentManager->method('deletePage')
            ->willThrowException(new StorageException('Storage error'));

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('withStatus')
            ->with(500)
            ->willReturnSelf();

        $result = $this->controller->delete($request, $response, ['slug' => 'test-page']);

        $this->assertSame($response, $result);
    }
}
