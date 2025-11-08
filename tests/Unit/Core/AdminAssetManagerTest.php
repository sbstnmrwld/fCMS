<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Core;

use FCMS\Core\AdminAssetManager;
use PHPUnit\Framework\TestCase;

class AdminAssetManagerTest extends TestCase
{
    private AdminAssetManager $assetManager;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/fcms_admin_test_' . uniqid();
        mkdir($this->tempDir . '/assets/css', 0755, true);
        $this->assetManager = new AdminAssetManager($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testCssReturnsUrlWithCacheBuster(): void
    {
        file_put_contents($this->tempDir . '/assets/css/style.css', 'test');
        $url = $this->assetManager->css('style.css');

        $this->assertStringStartsWith('/admin/assets/css/style.css?v=', $url);
    }

    public function testCssWithoutCacheBusting(): void
    {
        $manager = new AdminAssetManager($this->tempDir, '', false);
        $url = $manager->css('style.css');

        $this->assertEquals('/admin/assets/css/style.css', $url);
    }

    public function testJsReturnsUrlWithCacheBuster(): void
    {
        mkdir($this->tempDir . '/assets/js', 0755, true);
        file_put_contents($this->tempDir . '/assets/js/script.js', 'test');
        $url = $this->assetManager->js('script.js');

        $this->assertStringStartsWith('/admin/assets/js/script.js?v=', $url);
    }

    public function testImageReturnsUrl(): void
    {
        $url = $this->assetManager->image('logo.png');
        $this->assertEquals('/admin/assets/images/logo.png', $url);
    }

    public function testFontReturnsUrl(): void
    {
        $url = $this->assetManager->font('font.woff2');
        $this->assertEquals('/admin/assets/fonts/font.woff2', $url);
    }

    public function testBootstrapCssReturnsUrl(): void
    {
        $url = $this->assetManager->bootstrapCss();
        $this->assertEquals('/admin/assets/bootstrap/css/bootstrap.min.css', $url);
    }

    public function testBootstrapJsReturnsUrl(): void
    {
        $url = $this->assetManager->bootstrapJs();
        $this->assertEquals('/admin/assets/bootstrap/js/bootstrap.bundle.min.js', $url);
    }

    public function testBootstrapIconsReturnsUrl(): void
    {
        $url = $this->assetManager->bootstrapIcons();
        $this->assertEquals('/admin/assets/bootstrap-icons/bootstrap-icons.min.css', $url);
    }

    public function testAssetReturnsUrl(): void
    {
        $url = $this->assetManager->asset('custom/file.txt');
        $this->assertEquals('/admin/assets/custom/file.txt', $url);
    }

    public function testExistsReturnsTrueWhenFileExists(): void
    {
        file_put_contents($this->tempDir . '/assets/test.css', 'test');
        $this->assertTrue($this->assetManager->exists('test.css'));
    }

    public function testExistsReturnsFalseWhenFileNotExists(): void
    {
        $this->assertFalse($this->assetManager->exists('nonexistent.css'));
    }

    public function testPathReturnsPhysicalPath(): void
    {
        $path = $this->assetManager->path('test.css');
        $this->assertStringEndsWith('/assets/test.css', $path);
    }

    public function testConstructorWithBaseUrl(): void
    {
        $manager = new AdminAssetManager($this->tempDir, 'https://example.com');
        $url = $manager->image('logo.png');

        $this->assertEquals('https://example.com/admin/assets/images/logo.png', $url);
    }
}
