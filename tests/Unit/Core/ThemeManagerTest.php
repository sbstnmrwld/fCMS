<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Core;

use FCMS\Core\ThemeManager;
use FCMS\Core\ThemeAssetManager;
use FCMS\Core\LanguageManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests für ThemeManager
 */
class ThemeManagerTest extends TestCase
{
    private ThemeManager $themeManager;
    private string $tempThemesDir;
    private string $tempLangDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Erstelle temporäre Verzeichnisse
        $this->tempThemesDir = sys_get_temp_dir() . '/fcms_themes_test_' . uniqid();
        $this->tempLangDir = sys_get_temp_dir() . '/fcms_lang_test_' . uniqid();

        mkdir($this->tempThemesDir, 0755, true);
        mkdir($this->tempLangDir, 0755, true);

        // Erstelle Test-Theme
        $this->createTestTheme('default');

        $assetManager = new ThemeAssetManager($this->tempThemesDir, 'default');
        $languageManager = new LanguageManager($this->tempLangDir, 'de');

        $this->themeManager = new ThemeManager(
            $this->tempThemesDir,
            'default',
            $assetManager,
            $languageManager
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempThemesDir);
        $this->removeDirectory($this->tempLangDir);
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

    private function createTestTheme(string $name, array $info = []): void
    {
        $themePath = $this->tempThemesDir . '/' . $name;
        $templatesPath = $themePath . '/templates';

        mkdir($templatesPath, 0755, true);

        // Erstelle theme.json
        $defaultInfo = [
            'name' => $name,
            'title' => ucfirst($name) . ' Theme',
            'version' => '1.0.0',
            'author' => 'Test Author'
        ];

        file_put_contents(
            $themePath . '/theme.json',
            json_encode(array_merge($defaultInfo, $info), JSON_PRETTY_PRINT)
        );

        // Erstelle Test-Template
        file_put_contents(
            $templatesPath . '/test.php',
            '<?php echo "Test Template: " . $test; ?>'
        );
    }

    public function testGetActiveThemeReturnsThemeName(): void
    {
        $theme = $this->themeManager->getActiveTheme();

        $this->assertEquals('default', $theme);
    }

    public function testRenderTemplateWithData(): void
    {
        $output = $this->themeManager->render('test', ['test' => 'Hello']);

        $this->assertEquals('Test Template: Hello', $output);
    }

    public function testRenderThrowsExceptionForNonexistentTemplate(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->themeManager->render('nonexistent');
    }

    public function testGetThemeInfoReturnsThemeData(): void
    {
        $info = $this->themeManager->getThemeInfo();

        $this->assertIsArray($info);
        $this->assertEquals('default', $info['name']);
        $this->assertEquals('Default Theme', $info['title']);
    }

    public function testGetThemeInfoReturnsDefaultWhenNoJsonFile(): void
    {
        // Erstelle Theme ohne theme.json
        $themePath = $this->tempThemesDir . '/minimal';
        mkdir($themePath . '/templates', 0755, true);

        $assetManager = new ThemeAssetManager($this->tempThemesDir, 'minimal');
        $languageManager = new LanguageManager($this->tempLangDir, 'de');

        $manager = new ThemeManager(
            $this->tempThemesDir,
            'minimal',
            $assetManager,
            $languageManager
        );

        $info = $manager->getThemeInfo();

        $this->assertEquals(['name' => 'minimal'], $info);
    }

    public function testGetAvailableThemesReturnsAllThemes(): void
    {
        $this->createTestTheme('theme1');
        $this->createTestTheme('theme2');

        $themes = $this->themeManager->getAvailableThemes();

        $this->assertCount(3, $themes); // default + theme1 + theme2
        $this->assertArrayHasKey('default', $themes);
        $this->assertArrayHasKey('theme1', $themes);
        $this->assertArrayHasKey('theme2', $themes);
    }

    public function testSwitchThemeChangesActiveTheme(): void
    {
        $this->createTestTheme('new-theme');

        $result = $this->themeManager->switchTheme('new-theme');

        $this->assertTrue($result);
        $this->assertEquals('new-theme', $this->themeManager->getActiveTheme());
    }

    public function testSwitchThemeReturnsFalseForNonexistentTheme(): void
    {
        $result = $this->themeManager->switchTheme('nonexistent');

        $this->assertFalse($result);
        $this->assertEquals('default', $this->themeManager->getActiveTheme());
    }

    public function testRenderExtractsVariables(): void
    {
        $themePath = $this->tempThemesDir . '/default/templates';
        file_put_contents(
            $themePath . '/vars.php',
            '<?php echo $var1 . " " . $var2; ?>'
        );

        $output = $this->themeManager->render('vars', [
            'var1' => 'Hello',
            'var2' => 'World'
        ]);

        $this->assertEquals('Hello World', $output);
    }

    public function testRenderMakesAssetManagerAvailable(): void
    {
        $themePath = $this->tempThemesDir . '/default/templates';
        file_put_contents(
            $themePath . '/assets.php',
            '<?php echo get_class($assets); ?>'
        );

        $output = $this->themeManager->render('assets');

        $this->assertStringContainsString('ThemeAssetManager', $output);
    }

    public function testRenderMakesLanguageManagerAvailable(): void
    {
        $themePath = $this->tempThemesDir . '/default/templates';
        file_put_contents(
            $themePath . '/lang.php',
            '<?php echo get_class($lang); ?>'
        );

        $output = $this->themeManager->render('lang');

        $this->assertStringContainsString('LanguageManager', $output);
    }
}
