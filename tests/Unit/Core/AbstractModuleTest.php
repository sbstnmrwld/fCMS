<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Core;

use FCMS\Core\AbstractModule;
use FCMS\Core\ModuleInterface;
use PHPUnit\Framework\TestCase;

class AbstractModuleTest extends TestCase
{
    private ModuleInterface $module;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/fcms_module_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        // Erstelle module.json
        file_put_contents($this->tempDir . '/module.json', json_encode([
            'name' => 'test-module',
            'version' => '202511080000-dev',
            'title' => 'Test Module',
            'description' => 'A test module',
            'author' => 'Test Author',
            'requires' => ['fcms' => '202511080000-dev']
        ]));

        $this->module = new class($this->tempDir) extends AbstractModule {
            public function boot(object $app, object $container): void {
                // Mock implementation
            }
        };
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tempDir . '/*'));
        rmdir($this->tempDir);
        parent::tearDown();
    }

    public function testGetNameReturnsNameFromConfig(): void
    {
        $this->assertEquals('test-module', $this->module->getName());
    }

    public function testGetVersionReturnsVersionFromConfig(): void
    {
        $this->assertEquals('202511080000-dev', $this->module->getVersion());
    }

    public function testGetTitleReturnsTitleFromConfig(): void
    {
        $this->assertEquals('Test Module', $this->module->getTitle());
    }

    public function testGetDescriptionReturnsDescriptionFromConfig(): void
    {
        $this->assertEquals('A test module', $this->module->getDescription());
    }

    public function testGetAuthorReturnsAuthorFromConfig(): void
    {
        $this->assertEquals('Test Author', $this->module->getAuthor());
    }

    public function testGetRequiredFcmsVersionReturnsVersion(): void
    {
        $this->assertEquals('202511080000-dev', $this->module->getRequiredFcmsVersion());
    }

    public function testGetConfigReturnsFullConfig(): void
    {
        $config = $this->module->getConfig();

        $this->assertIsArray($config);
        $this->assertEquals('test-module', $config['name']);
    }

    public function testIsInstalledReturnsTrueWhenDirExists(): void
    {
        $this->assertTrue($this->module->isInstalled());
    }

    public function testActivateDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        $this->module->activate();
    }

    public function testDeactivateDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        $this->module->deactivate();
    }

    public function testInstallReturnsTrue(): void
    {
        $this->assertTrue($this->module->install());
    }

    public function testUninstallReturnsTrue(): void
    {
        $this->assertTrue($this->module->uninstall());
    }

    public function testGetPathReturnsModulePath(): void
    {
        $path = $this->module->getPath();
        $this->assertEquals($this->tempDir, $path);
    }

    public function testGetAdminAssetsReturnsEmptyArray(): void
    {
        $assets = $this->module->getAdminAssets();

        $this->assertIsArray($assets);
        $this->assertArrayHasKey('css', $assets);
        $this->assertArrayHasKey('js', $assets);
        $this->assertEmpty($assets['css']);
        $this->assertEmpty($assets['js']);
    }

    public function testFallbacksWhenNoConfigFile(): void
    {
        $tempDir2 = sys_get_temp_dir() . '/fcms_module_test2_' . uniqid();
        mkdir($tempDir2, 0755, true);

        $module2 = new class($tempDir2) extends AbstractModule {
            public function boot(object $app, object $container): void {}
        };

        $this->assertEquals(basename($tempDir2), $module2->getName());
        $this->assertEquals('000000000000-dev', $module2->getVersion());
        $this->assertEquals('Unknown', $module2->getAuthor());

        rmdir($tempDir2);
    }
}
