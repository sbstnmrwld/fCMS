<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Core;

use FCMS\Core\ContentManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests für Race Conditions in ContentManager
 */
class ContentManagerRaceConditionTest extends TestCase
{
    private string $tempDir;
    private ContentManager $contentManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/fcms_race_test_' . uniqid();
        mkdir($this->tempDir . '/pages', 0755, true);

        $this->contentManager = new ContentManager($this->tempDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Cleanup
        if (is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    /**
     * Testet, dass bei gleichzeitiger Slug-Generierung keine Duplikate entstehen
     */
    public function testConcurrentSlugGenerationCreatesUniquePages(): void
    {
        $pageData = [
            'title' => 'Test Page',
            'status' => 'published',
            'sections' => [],
            'nav_main' => false,
            'nav_footer' => false,
            'nav_order' => 0,
            'nav_label' => '',
            'meta_description' => '',
            'meta_keywords' => '',
        ];

        // Simuliere gleichzeitige Requests durch mehrere Prozesse
        // In einem echten Szenario würden hier Fork oder parallele HTTP-Requests verwendet
        // Für den Unit-Test testen wir das Locking direkt

        $results = [];
        $lockErrors = 0;

        // Führe mehrere "gleichzeitige" createPage-Aufrufe aus
        for ($i = 0; $i < 5; $i++) {
            try {
                $result = $this->contentManager->createPage($pageData);
                $this->assertTrue($result, "Page creation $i should succeed");
                $results[] = $result;
            } catch (\Exception $e) {
                // Erwartbar, wenn Lock nicht sofort verfügbar ist
                $lockErrors++;
            }
        }

        // Prüfe, dass alle Seiten erstellt wurden
        $this->assertGreaterThanOrEqual(1, count($results), 'Mindestens eine Seite sollte erstellt worden sein');

        // Prüfe, dass alle erstellten Seiten unterschiedliche Slugs haben
        $files = glob($this->tempDir . '/pages/*.json');
        $slugs = [];

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            $this->assertArrayHasKey('slug', $data);

            // Prüfe auf Duplikate
            $this->assertNotContains($data['slug'], $slugs, 'Slug "' . $data['slug'] . '" sollte eindeutig sein');
            $slugs[] = $data['slug'];
        }

        // Erwarte 5 unterschiedliche Seiten (test-page, test-page-1, test-page-2, etc.)
        $this->assertCount(5, $slugs, 'Es sollten 5 eindeutige Seiten erstellt worden sein');
        $this->assertContains('test-page', $slugs);
        $this->assertContains('test-page-1', $slugs);
        $this->assertContains('test-page-2', $slugs);
        $this->assertContains('test-page-3', $slugs);
        $this->assertContains('test-page-4', $slugs);
    }

    /**
     * Testet das Locking-Verhalten direkt
     */
    public function testFileLocksPreventSimultaneousAccess(): void
    {
        $lockFile = $this->tempDir . '/pages/.slug-creation.lock';

        // Erste Lock erwerben
        $handle1 = fopen($lockFile, 'c');
        $this->assertNotFalse($handle1);

        $locked1 = flock($handle1, LOCK_EX | LOCK_NB); // Non-blocking
        $this->assertTrue($locked1, 'Erste Lock sollte erfolgreich sein');

        // Zweite Lock versuchen (sollte fehlschlagen, da bereits gelockt)
        $handle2 = fopen($lockFile, 'c');
        $this->assertNotFalse($handle2);

        $locked2 = flock($handle2, LOCK_EX | LOCK_NB); // Non-blocking
        $this->assertFalse($locked2, 'Zweite Lock sollte fehlschlagen während erste aktiv ist');

        // Erste Lock freigeben
        flock($handle1, LOCK_UN);
        fclose($handle1);

        // Jetzt sollte zweite Lock erfolgreich sein
        $locked2Retry = flock($handle2, LOCK_EX | LOCK_NB);
        $this->assertTrue($locked2Retry, 'Lock sollte nach Freigabe verfügbar sein');

        flock($handle2, LOCK_UN);
        fclose($handle2);
    }

    /**
     * Testet, dass Lock-Datei korrekt aufgeräumt wird
     */
    public function testLockFileIsCreatedAndAccessible(): void
    {
        $pageData = [
            'title' => 'Test Lock File',
            'status' => 'published',
            'sections' => [],
            'nav_main' => false,
            'nav_footer' => false,
            'nav_order' => 0,
            'nav_label' => '',
            'meta_description' => '',
            'meta_keywords' => '',
        ];

        $lockFile = $this->tempDir . '/pages/.slug-creation.lock';

        // Lock-Datei sollte vor createPage nicht existieren
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }

        // Erstelle Seite (sollte Lock-Datei erstellen)
        $result = $this->contentManager->createPage($pageData);
        $this->assertTrue($result);

        // Lock-Datei sollte existieren (wird nicht gelöscht, nur freigegeben)
        $this->assertFileExists($lockFile, 'Lock-Datei sollte nach createPage existieren');

        // Prüfe, dass Lock-Datei nicht mehr gelockt ist
        $handle = fopen($lockFile, 'c');
        $locked = flock($handle, LOCK_EX | LOCK_NB);
        $this->assertTrue($locked, 'Lock-Datei sollte nach createPage nicht mehr gelockt sein');
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
