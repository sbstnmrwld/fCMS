<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Core;

use FCMS\Core\LanguageManager;
use PHPUnit\Framework\TestCase;

class LanguageManagerTest extends TestCase
{
    private LanguageManager $languageManager;
    private string $tempLangDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempLangDir = sys_get_temp_dir() . '/fcms_lang_test_' . uniqid();
        mkdir($this->tempLangDir, 0755, true);
        
        // Erstelle de.json
        file_put_contents($this->tempLangDir . '/de.json', json_encode([
            'hello' => 'Hallo',
            'welcome' => 'Willkommen {name}',
            'nested' => ['key' => 'Verschachtelt']
        ]));
        
        // Erstelle en.json
        file_put_contents($this->tempLangDir . '/en.json', json_encode([
            'hello' => 'Hello',
            'welcome' => 'Welcome {name}'
        ]));
        
        $this->languageManager = new LanguageManager($this->tempLangDir, 'de');
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tempLangDir . '/*'));
        rmdir($this->tempLangDir);
        parent::tearDown();
    }

    public function testGetCurrentLanguageReturnsDefault(): void
    {
        $this->assertEquals('de', $this->languageManager->getCurrentLanguage());
    }

    public function testTranslateReturnsTranslation(): void
    {
        $this->assertEquals('Hallo', $this->languageManager->translate('hello'));
    }

    public function testTranslateWithParameters(): void
    {
        $result = $this->languageManager->translate('welcome', ['name' => 'John']);
        $this->assertEquals('Willkommen John', $result);
    }

    public function testTranslateNestedKey(): void
    {
        $result = $this->languageManager->translate('nested.key');
        $this->assertEquals('Verschachtelt', $result);
    }

    public function testTranslateReturnsKeyWhenNotFound(): void
    {
        $result = $this->languageManager->translate('nonexistent');
        $this->assertEquals('nonexistent', $result);
    }

    public function testTIsShortcutForTranslate(): void
    {
        $this->assertEquals('Hallo', $this->languageManager->t('hello'));
    }

    public function testSetLanguageChangesCurrentLanguage(): void
    {
        $this->languageManager->setLanguage('en');
        $this->assertEquals('en', $this->languageManager->getCurrentLanguage());
    }

    public function testSetLanguageLoadsNewTranslations(): void
    {
        $this->languageManager->setLanguage('en');
        $this->assertEquals('Hello', $this->languageManager->translate('hello'));
    }

    public function testFallsBackToDeutschForMissingLanguage(): void
    {
        $this->languageManager->setLanguage('fr');
        $this->assertEquals('Hallo', $this->languageManager->translate('hello'));
    }

    public function testGetAvailableLanguagesReturnsAllLanguages(): void
    {
        $languages = $this->languageManager->getAvailableLanguages();
        $this->assertCount(2, $languages);
        $this->assertContains('de', $languages);
        $this->assertContains('en', $languages);
    }

    public function testHasLanguageReturnsTrueWhenExists(): void
    {
        $this->assertTrue($this->languageManager->hasLanguage('de'));
    }

    public function testHasLanguageReturnsFalseWhenNotExists(): void
    {
        $this->assertFalse($this->languageManager->hasLanguage('fr'));
    }

    public function testCachesTranslations(): void
    {
        $this->languageManager->translate('hello');
        $this->languageManager->setLanguage('en');
        $this->languageManager->setLanguage('de');
        $this->assertEquals('Hallo', $this->languageManager->translate('hello'));
    }

    public function testFallbackToDeutschForMissingKey(): void
    {
        $this->languageManager->setLanguage('en');
        $result = $this->languageManager->translate('nested.key');
        $this->assertEquals('Verschachtelt', $result);
    }
}
