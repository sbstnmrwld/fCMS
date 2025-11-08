<?php

declare(strict_types=1);

namespace FCMS\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration-Test für Race Conditions bei gleichzeitigen Page-Erstellungen
 * 
 * Dieser Test simuliert echte parallele HTTP-Requests und prüft,
 * dass keine Slug-Duplikate entstehen.
 */
class PageCreationRaceConditionTest extends TestCase
{
    private const BASE_URL = 'http://localhost:8000';
    private string $authCookie = '';
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Prüfe ob Server läuft
        $ch = curl_init(self::BASE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 0) {
            $this->markTestSkipped('Server nicht erreichbar auf ' . self::BASE_URL);
        }
        
        // Authentifiziere für Admin-Zugriff
        $this->authCookie = $this->login();
        
        if (empty($this->authCookie)) {
            $this->markTestSkipped('Konnte nicht authentifizieren - Login fehlgeschlagen');
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Cleanup: Lösche alle Test-Seiten
        $this->cleanupTestPages();
    }

    /**
     * Testet gleichzeitige Page-Erstellung mit identischen Titeln
     */
    public function testConcurrentPageCreationWithIdenticalTitles(): void
    {
        $timestamp = time();
        $title = "Race Condition Test {$timestamp}";
        
        // Erstelle mehrere parallele Requests
        $multiHandle = curl_multi_init();
        $handles = [];
        $numRequests = 5;

        for ($i = 0; $i < $numRequests; $i++) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => self::BASE_URL . '/admin/pages/create',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'title' => $title,
                    'status' => 'published',
                    'sections' => json_encode([]),
                    'nav_main' => '0',
                    'nav_footer' => '0',
                    'nav_order' => '0',
                    'nav_label' => '',
                    'meta_description' => '',
                    'meta_keywords' => '',
                ]),
                CURLOPT_HTTPHEADER => [
                    'Cookie: ' . $this->authCookie,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
            ]);
            
            curl_multi_add_handle($multiHandle, $ch);
            $handles[] = $ch;
        }

        // Führe alle Requests parallel aus
        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        // Sammle Ergebnisse
        $slugs = [];
        $successCount = 0;

        foreach ($handles as $ch) {
            $response = curl_multi_getcontent($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // 302/303 = Erfolgreicher Redirect nach Page-Erstellung
            if ($statusCode === 302 || $statusCode === 303) {
                $successCount++;
            }
            
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }
        
        curl_multi_close($multiHandle);
        
        // Warte kurz, damit alle Dateien geschrieben sind
        usleep(100000); // 100ms
        
        // Lade alle erstellten Test-Seiten und prüfe ihre Slugs
        $slugs = $this->getTestPageSlugs($title);

        // Assertions
        $this->assertGreaterThanOrEqual(1, $successCount, 'Mindestens eine Page sollte erfolgreich erstellt werden');
        $this->assertEquals($numRequests, $successCount, 'Alle Requests sollten erfolgreich sein');
        $this->assertCount($numRequests, $slugs, 'Alle Seiten sollten in der Datenbank sein');
        
        // Wichtigster Test: Alle Slugs müssen eindeutig sein
        $uniqueSlugs = array_unique($slugs);
        $this->assertCount(
            count($slugs),
            $uniqueSlugs,
            'Alle Slugs müssen eindeutig sein - keine Duplikate durch Race Condition: ' . implode(', ', $slugs)
        );

        // Erwarte sequentielle Nummerierung
        sort($slugs);
        $expectedSlug = $this->slugify($title);
        $this->assertEquals($expectedSlug, $slugs[0], 'Erster Slug sollte ohne Nummer sein');
        
        for ($i = 1; $i < count($slugs); $i++) {
            $this->assertEquals(
                $expectedSlug . '-' . $i,
                $slugs[$i],
                "Slug {$i} sollte korrekt nummeriert sein"
            );
        }
    }

    /**
     * Testet Race Condition bei sehr schnell aufeinanderfolgenden Requests
     */
    public function testRapidSequentialPageCreation(): void
    {
        $timestamp = time();
        $title = "Rapid Test {$timestamp}";
        $slugs = [];

        // Schnelle sequentielle Erstellung (simuliert schnelle User-Klicks)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->createPage([
                'title' => $title,
                'status' => 'published',
                'sections' => [],
                'nav_main' => false,
                'nav_footer' => false,
                'nav_order' => 0,
                'nav_label' => '',
                'meta_description' => '',
                'meta_keywords' => '',
            ]);

            $this->assertArrayHasKey('slug', $response);
            $slugs[] = $response['slug'];
            
            // Sehr kurze Pause (simuliert schnelle Klicks)
            usleep(10000); // 10ms
        }

        // Alle Slugs müssen eindeutig sein
        $this->assertCount(3, array_unique($slugs), 'Alle Slugs müssen eindeutig sein');
    }

    /**
     * Hilfsmethode: Login durchführen
     */
    private function login(): string
    {
        // Für den Test verwenden wir einen vordefinierten Admin-Account
        // In der echten Umgebung sollte das über Env-Variablen kommen
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::BASE_URL . '/admin/login',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'username' => 'admin',
                'password' => 'admin', // Default-Passwort für Tests
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        // Extrahiere Session-Cookie
        preg_match('/Set-Cookie:\s*([^;]+)/i', $response, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Hilfsmethode: Seite erstellen
     */
    private function createPage(array $data): array
    {
        // Verwende Form-POST statt JSON-API
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::BASE_URL . '/admin/pages/create',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Cookie: ' . $this->authCookie,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false, // Nicht automatisch redirects folgen
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Bei Erfolg wird zur Seiten-Liste redirected (302)
        if ($httpCode === 302 || $httpCode === 303) {
            // Extrahiere Slug aus der gespeicherten Seite
            // Da wir keinen direkten Zugriff haben, müssen wir die Seiten-Liste abrufen
            return ['slug' => $this->slugify($data['title'])];
        }

        return [];
    }

    /**
     * Hilfsmethode: Hole alle Test-Seiten-Slugs mit bestimmtem Titel-Präfix
     */
    private function getTestPageSlugs(string $titlePrefix): array
    {
        $contentPath = __DIR__ . '/../../content/pages';
        
        if (!is_dir($contentPath)) {
            return [];
        }
        
        $slugs = [];
        $files = glob($contentPath . '/*.json');
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (isset($data['title']) && str_starts_with($data['title'], $titlePrefix)) {
                $slugs[] = $data['slug'];
            }
        }
        
        sort($slugs);
        return $slugs;
    }

    /**
     * Hilfsmethode: Erstelle Slug aus Titel
     */
    private function slugify(string $title): string
    {
        $slug = str_replace(
            ['ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'],
            ['ae', 'oe', 'ue', 'ss', 'ae', 'oe', 'ue'],
            $title
        );
        $slug = preg_replace('/[^a-z0-9-]+/i', '-', $slug);
        return strtolower(trim($slug, '-'));
    }

    /**
     * Hilfsmethode: Lösche Test-Seiten
     */
    private function cleanupTestPages(): void
    {
        $contentPath = __DIR__ . '/../../content/pages';
        
        if (!is_dir($contentPath)) {
            return;
        }
        
        $files = glob($contentPath . '/*.json');
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (isset($data['title']) && 
                (str_contains($data['title'], 'Race Condition Test') || 
                 str_contains($data['title'], 'Rapid Test'))) {
                @unlink($file);
            }
        }
    }
}
