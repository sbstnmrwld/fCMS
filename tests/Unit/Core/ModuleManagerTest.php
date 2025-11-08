<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Core;

use FCMS\Core\ModuleManager;
use FCMS\Core\ModuleInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests für ModuleManager
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ModuleManagerTest extends TestCase
{
    private ModuleManager $moduleManager;
    private string $tempModulesDir;
    private string $tempDataDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Erstelle temporäre Verzeichnisse für Tests
        $this->tempModulesDir = sys_get_temp_dir() . '/fcms_modules_test_' . uniqid();
        $this->tempDataDir = sys_get_temp_dir() . '/fcms_data_test_' . uniqid();

        mkdir($this->tempModulesDir, 0755, true);
        mkdir($this->tempDataDir, 0755, true);

        $this->moduleManager = new ModuleManager($this->tempModulesDir, $this->tempDataDir);
    }

    protected function tearDown(): void
    {
        // Räume temporäre Verzeichnisse auf
        $this->removeDirectory($this->tempModulesDir);
        $this->removeDirectory($this->tempDataDir);

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

    private function createMockModule(string $name): void
    {
        $moduleDir = $this->tempModulesDir . '/' . $name;
        mkdir($moduleDir, 0755, true);

        $className = $this->toPascalCase($name) . 'Module';
        $moduleFile = $moduleDir . '/' . $className . '.php';

        $code = <<<PHP
<?php

namespace FCMS\Modules;

use FCMS\Core\ModuleInterface;

class {$className} implements ModuleInterface
{
    private string \$moduleDir;

    public function __construct(string \$moduleDir)
    {
        \$this->moduleDir = \$moduleDir;
    }

    public function getName(): string
    {
        return '{$name}';
    }

    public function getVersion(): string
    {
        return '202511080000-dev';
    }

    public function getTitle(): string
    {
        return 'Test Module';
    }

    public function getDescription(): string
    {
        return 'A test module';
    }

    public function getAuthor(): string
    {
        return 'Test Author';
    }

    public function getRequiredFcmsVersion(): string
    {
        return '202511080000-dev';
    }

    public function activate(): void
    {
        // Mock activation
    }

    public function deactivate(): void
    {
        // Mock deactivation
    }

    public function boot(object \$app, object \$container): void
    {
        // Mock boot
    }

    public function getConfig(): array
    {
        return [];
    }

    public function isInstalled(): bool
    {
        return true;
    }

    public function install(): bool
    {
        return true;
    }

    public function uninstall(): bool
    {
        return true;
    }
}
PHP;

        file_put_contents($moduleFile, $code);
    }

    private function toPascalCase(string $string): string
    {
        return str_replace('-', '', ucwords($string, '-'));
    }

    public function testGetAllModulesReturnsEmptyArrayInitially(): void
    {
        $modules = $this->moduleManager->getAllModules();

        $this->assertIsArray($modules);
        $this->assertEmpty($modules);
    }

    public function testDiscoverModulesFindsModules(): void
    {
        $this->createMockModule('test-module');

        $this->moduleManager->discoverModules();

        $modules = $this->moduleManager->getAllModules();

        $this->assertCount(1, $modules);
    }

    public function testGetModuleReturnsNullForNonexistent(): void
    {
        $module = $this->moduleManager->getModule('nonexistent');

        $this->assertNull($module);
    }

    public function testGetModuleReturnsModuleInstance(): void
    {
        $this->createMockModule('test-module');
        $this->moduleManager->discoverModules();

        $module = $this->moduleManager->getModule('test-module');

        $this->assertInstanceOf(ModuleInterface::class, $module);
    }

    public function testIsActiveReturnsFalseInitially(): void
    {
        $this->createMockModule('test-module');
        $this->moduleManager->discoverModules();

        $isActive = $this->moduleManager->isActive('test-module');

        $this->assertFalse($isActive);
    }

    public function testActivateActivatesModule(): void
    {
        $this->createMockModule('test-module');
        $this->moduleManager->discoverModules();

        $result = $this->moduleManager->activate('test-module');

        $this->assertTrue($result);
        $this->assertTrue($this->moduleManager->isActive('test-module'));
    }

    public function testActivateReturnsFalseForNonexistentModule(): void
    {
        $result = $this->moduleManager->activate('nonexistent');

        $this->assertFalse($result);
    }

    public function testActivateReturnsTrueIfAlreadyActive(): void
    {
        $this->createMockModule('test-module');
        $this->moduleManager->discoverModules();

        $this->moduleManager->activate('test-module');
        $result = $this->moduleManager->activate('test-module');

        $this->assertTrue($result);
    }

    public function testDeactivateDeactivatesModule(): void
    {
        $this->createMockModule('test-module');
        $this->moduleManager->discoverModules();

        $this->moduleManager->activate('test-module');
        $result = $this->moduleManager->deactivate('test-module');

        $this->assertTrue($result);
        $this->assertFalse($this->moduleManager->isActive('test-module'));
    }

    public function testDeactivateReturnsFalseForNonexistentModule(): void
    {
        $result = $this->moduleManager->deactivate('nonexistent');

        $this->assertFalse($result);
    }

    public function testDeactivateReturnsTrueIfAlreadyInactive(): void
    {
        $this->createMockModule('test-module');
        $this->moduleManager->discoverModules();

        $result = $this->moduleManager->deactivate('test-module');

        $this->assertTrue($result);
    }

    public function testGetActiveModulesReturnsEmptyArrayInitially(): void
    {
        $activeModules = $this->moduleManager->getActiveModules();

        $this->assertIsArray($activeModules);
        $this->assertEmpty($activeModules);
    }

    public function testGetActiveModulesReturnsActiveModuleNames(): void
    {
        $this->createMockModule('test-module');
        $this->moduleManager->discoverModules();
        $this->moduleManager->activate('test-module');

        $activeModules = $this->moduleManager->getActiveModules();

        $this->assertCount(1, $activeModules);
        $this->assertContains('test-module', $activeModules);
    }

    public function testGetModuleInfoReturnsModuleData(): void
    {
        $this->createMockModule('test-module');
        $this->moduleManager->discoverModules();

        $info = $this->moduleManager->getModuleInfo('test-module');

        $this->assertIsArray($info);
        $this->assertEquals('test-module', $info['name']);
        $this->assertEquals('Test Module', $info['title']);
        $this->assertArrayHasKey('version', $info);
        $this->assertArrayHasKey('is_active', $info);
    }

    public function testGetModuleInfoReturnsEmptyArrayForNonexistent(): void
    {
        $info = $this->moduleManager->getModuleInfo('nonexistent');

        $this->assertIsArray($info);
        $this->assertEmpty($info);
    }

    public function testGetAllModuleInfoReturnsAllModules(): void
    {
        $this->createMockModule('module-1');
        $this->createMockModule('module-2');
        $this->moduleManager->discoverModules();

        $info = $this->moduleManager->getAllModuleInfo();

        $this->assertCount(2, $info);
        $this->assertArrayHasKey('module-1', $info);
        $this->assertArrayHasKey('module-2', $info);
    }

    public function testActiveModulesPersistAcrossInstances(): void
    {
        $this->createMockModule('test-module');
        $this->moduleManager->discoverModules();
        $this->moduleManager->activate('test-module');

        // Erstelle neue Instanz
        $newManager = new ModuleManager($this->tempModulesDir, $this->tempDataDir);

        $activeModules = $newManager->getActiveModules();

        $this->assertContains('test-module', $activeModules);
    }
}
