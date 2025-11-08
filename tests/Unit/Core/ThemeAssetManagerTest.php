<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Core;

use FCMS\Core\ThemeAssetManager;
use PHPUnit\Framework\TestCase;

class ThemeAssetManagerTest extends TestCase
{
    private ThemeAssetManager $assetManager;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/fcms_themes_test_' . uniqid();
        mkdir($this->tempDir . '/default/assets', 0755, true);
        $this->assetManager = new ThemeAssetManager($this->tempDir, 'default');
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

    public function testCssReturnsCorrectUrl(): void
    {
        $url = $this->assetManager->css('style.css');
        $this->assertEquals('/themes/default/assets/css/style.css', $url);
    }

    public function testJsReturnsCorrectUrl(): void
    {
        $url = $this->assetManager->js('script.js');
        $this->assertEquals('/themes/default/assets/js/script.js', $url);
    }

    public function testImageReturnsCorrectUrl(): void
    {
        $url = $this->assetManager->image('logo.png');
        $this->assertEquals('/themes/default/assets/images/logo.png', $url);
    }

    public function testFontReturnsCorrectUrl(): void
    {
        $url = $this->assetManager->font('font.woff2');
        $this->assertEquals('/themes/default/assets/fonts/font.woff2', $url);
    }

    public function testBootstrapCssReturnsCorrectUrl(): void
    {
        $url = $this->assetManager->bootstrapCss();
        $this->assertEquals('/themes/default/assets/bootstrap/css/bootstrap.min.css', $url);
    }

    public function testBootstrapJsReturnsCorrectUrl(): void
    {
        $url = $this->assetManager->bootstrapJs();
        $this->assertEquals('/themes/default/assets/bootstrap/js/bootstrap.bundle.min.js', $url);
    }

    public function testAssetReturnsCorrectUrl(): void
    {
        $url = $this->assetManager->asset('custom/file.txt');
        $this->assertEquals('/themes/default/assets/custom/file.txt', $url);
    }

    public function testExistsReturnsTrueWhenFileExists(): void
    {
        file_put_contents($this->tempDir . '/default/assets/test.css', 'test');
        $this->assertTrue($this->assetManager->exists('test.css'));
    }

    public function testExistsReturnsFalseWhenFileNotExists(): void
    {
        $this->assertFalse($this->assetManager->exists('nonexistent.css'));
    }

    public function testPathReturnsPhysicalPath(): void
    {
        $path = $this->assetManager->path('test.css');
        $this->assertStringContainsString('default/assets/test.css', $path);
    }

    public function testGetTemplatePathReturnsCorrectPath(): void
    {
        $path = $this->assetManager->getTemplatePath();
        $this->assertStringEndsWith('/default/templates', $path);
    }

    public function testGetActiveThemeReturnsThemeName(): void
    {
        $this->assertEquals('default', $this->assetManager->getActiveTheme());
    }

    public function testSwitchThemeChangesActiveTheme(): void
    {
        mkdir($this->tempDir . '/new-theme/assets', 0755, true);
        $this->assetManager->switchTheme('new-theme');
        $this->assertEquals('new-theme', $this->assetManager->getActiveTheme());
    }

    public function testUrlsStripLeadingSlashes(): void
    {
        $url = $this->assetManager->css('/style.css');
        $this->assertEquals('/themes/default/assets/css/style.css', $url);
    }

    public function testConstructorWithBaseUrl(): void
    {
        $manager = new ThemeAssetManager($this->tempDir, 'default', 'https://example.com');
        $url = $manager->css('style.css');
        $this->assertEquals('https://example.com/themes/default/assets/css/style.css', $url);
    }
}
