<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Core;

use FCMS\Core\ContentManager;
use FCMS\Exceptions\ValidationException;
use FCMS\Exceptions\NotFoundException;
use FCMS\Exceptions\StorageException;
use PHPUnit\Framework\TestCase;

/**
 * Tests für ContentManager
 */
class ContentManagerTest extends TestCase
{
    private ContentManager $contentManager;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Erstelle temporäres Verzeichnis für Tests
        $this->tempDir = sys_get_temp_dir() . '/fcms_content_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        $this->contentManager = new ContentManager($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Räume temporäres Verzeichnis auf
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function getValidPageData(): array
    {
        return [
            'title' => 'Test Page',
            'status' => 'published',
            'sections' => [],
            'nav_main' => true,
            'nav_footer' => false,
            'nav_order' => 10,
            'nav_label' => 'Test',
            'meta_description' => 'Test description',
            'meta_keywords' => 'test, keywords',
        ];
    }

    public function testCreatePageWithValidData(): void
    {
        $data = $this->getValidPageData();

        $result = $this->contentManager->createPage($data);

        $this->assertTrue($result);
    }

    public function testCreatePageGeneratesSlugFromTitle(): void
    {
        $data = $this->getValidPageData();

        $this->contentManager->createPage($data);

        $page = $this->contentManager->getPage('test-page');

        $this->assertNotNull($page);
        $this->assertEquals('test-page', $page['slug']);
    }

    public function testCreatePageWithCustomSlug(): void
    {
        $data = $this->getValidPageData();
        $data['slug'] = 'custom-slug';

        $this->contentManager->createPage($data);

        $page = $this->contentManager->getPage('custom-slug');

        $this->assertNotNull($page);
        $this->assertEquals('custom-slug', $page['slug']);
    }

    public function testCreatePageThrowsExceptionForEmptyTitle(): void
    {
        $data = $this->getValidPageData();
        $data['title'] = '';

        $this->expectException(ValidationException::class);

        $this->contentManager->createPage($data);
    }

    public function testCreatePageThrowsExceptionForInvalidStatus(): void
    {
        $data = $this->getValidPageData();
        $data['status'] = 'invalid_status';

        $this->expectException(ValidationException::class);

        $this->contentManager->createPage($data);
    }

    public function testCreatePageRejectsPathTraversalInSlug(): void
    {
        $data = $this->getValidPageData();
        $data['slug'] = '../etc/passwd';

        $this->expectException(ValidationException::class);

        $this->contentManager->createPage($data);
    }

    public function testCreatePageEnsuresUniqueSlug(): void
    {
        $data = $this->getValidPageData();

        $this->contentManager->createPage($data);
        $this->contentManager->createPage($data);

        $page1 = $this->contentManager->getPage('test-page');
        $page2 = $this->contentManager->getPage('test-page-1');

        $this->assertNotNull($page1);
        $this->assertNotNull($page2);
    }

    public function testGetPageReturnsNullWhenNotFound(): void
    {
        $page = $this->contentManager->getPage('nonexistent');

        $this->assertNull($page);
    }

    public function testGetPageReturnsPageData(): void
    {
        $data = $this->getValidPageData();
        $this->contentManager->createPage($data);

        $page = $this->contentManager->getPage('test-page');

        $this->assertIsArray($page);
        $this->assertEquals('Test Page', $page['title']);
        $this->assertEquals('published', $page['status']);
    }

    public function testGetPageThrowsExceptionForInvalidSlug(): void
    {
        $this->expectException(ValidationException::class);

        $this->contentManager->getPage('../etc/passwd');
    }

    public function testUpdatePageUpdatesTitle(): void
    {
        $data = $this->getValidPageData();
        $this->contentManager->createPage($data);

        $this->contentManager->updatePage('test-page', ['title' => 'Updated Title']);

        $page = $this->contentManager->getPage('test-page');

        $this->assertEquals('Updated Title', $page['title']);
    }

    public function testUpdatePageUpdatesStatus(): void
    {
        $data = $this->getValidPageData();
        $this->contentManager->createPage($data);

        $this->contentManager->updatePage('test-page', ['status' => 'draft']);

        $page = $this->contentManager->getPage('test-page');

        $this->assertEquals('draft', $page['status']);
    }

    public function testUpdatePageThrowsNotFoundForNonexistentPage(): void
    {
        $this->expectException(NotFoundException::class);

        $this->contentManager->updatePage('nonexistent', ['title' => 'Test']);
    }

    public function testUpdatePageValidatesNewData(): void
    {
        $data = $this->getValidPageData();
        $this->contentManager->createPage($data);

        $this->expectException(ValidationException::class);

        $this->contentManager->updatePage('test-page', ['status' => 'invalid']);
    }

    public function testUpdatePageUpdatesTimestamp(): void
    {
        $data = $this->getValidPageData();
        $this->contentManager->createPage($data);

        $page1 = $this->contentManager->getPage('test-page');
        sleep(1);

        $this->contentManager->updatePage('test-page', ['title' => 'Updated']);

        $page2 = $this->contentManager->getPage('test-page');

        $this->assertNotEquals($page1['updated_at'], $page2['updated_at']);
    }

    public function testDeletePageRemovesPage(): void
    {
        $data = $this->getValidPageData();
        $this->contentManager->createPage($data);

        $this->contentManager->deletePage('test-page');

        $page = $this->contentManager->getPage('test-page');

        $this->assertNull($page);
    }

    public function testDeletePageThrowsNotFoundForNonexistentPage(): void
    {
        $this->expectException(NotFoundException::class);

        $this->contentManager->deletePage('nonexistent');
    }

    public function testDeletePageValidatesSlug(): void
    {
        $this->expectException(ValidationException::class);

        $this->contentManager->deletePage('../etc/passwd');
    }

    public function testGetAllPagesReturnsEmptyArrayInitially(): void
    {
        $pages = $this->contentManager->getAllPages();

        $this->assertIsArray($pages);
        $this->assertEmpty($pages);
    }

    public function testGetAllPagesReturnsAllPages(): void
    {
        $this->contentManager->createPage(['title' => 'Page 1'] + $this->getValidPageData());
        $this->contentManager->createPage(['title' => 'Page 2'] + $this->getValidPageData());

        $pages = $this->contentManager->getAllPages();

        $this->assertCount(2, $pages);
    }

    public function testGetAllPagesSortsByNavigationOrder(): void
    {
        $data1 = $this->getValidPageData();
        $data1['title'] = 'Second';
        $data1['nav_order'] = 20;

        $data2 = $this->getValidPageData();
        $data2['title'] = 'First';
        $data2['nav_order'] = 10;

        $this->contentManager->createPage($data1);
        $this->contentManager->createPage($data2);

        $pages = $this->contentManager->getAllPages();

        $this->assertEquals('First', $pages[0]['title']);
        $this->assertEquals('Second', $pages[1]['title']);
    }

    public function testGetPublishedPagesReturnsOnlyPublished(): void
    {
        $published = $this->getValidPageData();
        $published['title'] = 'Published';
        $published['status'] = 'published';

        $draft = $this->getValidPageData();
        $draft['title'] = 'Draft';
        $draft['status'] = 'draft';

        $this->contentManager->createPage($published);
        $this->contentManager->createPage($draft);

        $pages = $this->contentManager->getPublishedPages();
        $pages = array_values($pages); // Re-index array

        $this->assertCount(1, $pages);
        $this->assertEquals('Published', $pages[0]['title']);
    }

    public function testGetNavigationPagesReturnsMainNavPages(): void
    {
        $mainNav = $this->getValidPageData();
        $mainNav['title'] = 'Main Nav';
        $mainNav['nav_main'] = true;

        $noNav = $this->getValidPageData();
        $noNav['title'] = 'No Nav';
        $noNav['nav_main'] = false;

        $this->contentManager->createPage($mainNav);
        $this->contentManager->createPage($noNav);

        $pages = $this->contentManager->getNavigationPages('main');
        $pages = array_values($pages); // Re-index array

        $this->assertCount(1, $pages);
        $this->assertEquals('Main Nav', $pages[0]['title']);
    }

    public function testPageExistsReturnsTrueWhenExists(): void
    {
        $data = $this->getValidPageData();
        $this->contentManager->createPage($data);

        $exists = $this->contentManager->pageExists('test-page');

        $this->assertTrue($exists);
    }

    public function testPageExistsReturnsFalseWhenNotExists(): void
    {
        $exists = $this->contentManager->pageExists('nonexistent');

        $this->assertFalse($exists);
    }

    public function testPageExistsValidatesSlug(): void
    {
        $this->expectException(ValidationException::class);

        $this->contentManager->pageExists('../etc/passwd');
    }

    public function testCreatePageStoresAllMetadata(): void
    {
        $data = $this->getValidPageData();
        $this->contentManager->createPage($data);

        $page = $this->contentManager->getPage('test-page');

        $this->assertArrayHasKey('id', $page);
        $this->assertArrayHasKey('created_at', $page);
        $this->assertArrayHasKey('updated_at', $page);
        $this->assertArrayHasKey('navigation', $page);
        $this->assertArrayHasKey('meta', $page);
    }
}
