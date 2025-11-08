<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Core;

use FCMS\Core\SettingsManager;
use PHPUnit\Framework\TestCase;

class SettingsManagerTest extends TestCase
{
    private SettingsManager $settingsManager;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/fcms_settings_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->settingsManager = new SettingsManager($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempDir . '/settings.json')) {
            unlink($this->tempDir . '/settings.json');
        }
        rmdir($this->tempDir);
        parent::tearDown();
    }

    public function testGetReturnsDefaultValue(): void
    {
        $value = $this->settingsManager->get('nonexistent', 'default');
        $this->assertEquals('default', $value);
    }

    public function testGetReturnsNestedValue(): void
    {
        $value = $this->settingsManager->get('site.name');
        $this->assertEquals('Meine Website', $value);
    }

    public function testSetStoresValue(): void
    {
        $this->settingsManager->set('test.key', 'test_value');
        $this->assertEquals('test_value', $this->settingsManager->get('test.key'));
    }

    public function testSetCreatesNestedStructure(): void
    {
        $this->settingsManager->set('new.nested.key', 'value');
        $this->assertEquals('value', $this->settingsManager->get('new.nested.key'));
    }

    public function testAllReturnsAllSettings(): void
    {
        $all = $this->settingsManager->all();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('site', $all);
    }

    public function testUpdateUpdatesMultipleSettings(): void
    {
        $this->settingsManager->update([
            'site.name' => 'New Name',
            'site.tagline' => 'New Tagline'
        ]);
        
        $this->assertEquals('New Name', $this->settingsManager->get('site.name'));
        $this->assertEquals('New Tagline', $this->settingsManager->get('site.tagline'));
    }

    public function testResetResetsToDefaults(): void
    {
        $this->settingsManager->set('site.name', 'Changed');
        $this->settingsManager->reset();
        
        $this->assertEquals('Meine Website', $this->settingsManager->get('site.name'));
    }

    public function testPersistsAcrossInstances(): void
    {
        $this->settingsManager->set('test', 'value');
        
        $newManager = new SettingsManager($this->tempDir);
        $this->assertEquals('value', $newManager->get('test'));
    }

    public function testUpdatesTimestamp(): void
    {
        $before = $this->settingsManager->get('updated_at');
        sleep(1);
        $this->settingsManager->set('test', 'value');
        $after = $this->settingsManager->get('updated_at');
        
        $this->assertNotEquals($before, $after);
    }

    public function testGetReturnsNullWhenNotFoundAndNoDefault(): void
    {
        $this->assertNull($this->settingsManager->get('nonexistent'));
    }

    public function testDefaultSettingsHaveRequiredKeys(): void
    {
        $all = $this->settingsManager->all();
        $this->assertArrayHasKey('site', $all);
        $this->assertArrayHasKey('seo', $all);
        $this->assertArrayHasKey('theme', $all);
        $this->assertArrayHasKey('maintenance', $all);
    }
}
