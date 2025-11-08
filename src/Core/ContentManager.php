<?php

declare(strict_types=1);

namespace FCMS\Core;

use Symfony\Component\Filesystem\Filesystem;
use FCMS\Exceptions\ValidationException;
use FCMS\Exceptions\NotFoundException;
use FCMS\Exceptions\StorageException;

/**
 * Content-Manager
 *
 * Verwaltet Seiten und Inhalte im dateibasierten JSON-Format.
 * Implementiert File-Locking für sichere gleichzeitige Zugriffe.
 */
class ContentManager
{
    private string $contentPath;
    private Filesystem $filesystem;

    public function __construct(string $contentPath)
    {
        $this->contentPath = $contentPath . '/pages';
        $this->filesystem = new Filesystem();

        // Stelle sicher, dass Content-Verzeichnis existiert
        if (!is_dir($this->contentPath)) {
            $this->filesystem->mkdir($this->contentPath, 0755);
        }
    }

    /**
     * Erstellt eine neue Seite
     *
     * @param array $data Die Seitendaten
     * @return bool True bei Erfolg
     * @throws ValidationException Bei ungültigen Daten
     * @throws StorageException Bei Speicherfehlern
     */
    public function createPage(array $data): bool
    {
        // Validiere alle Eingabedaten
        $validated = Validator::pageData($data);

        // Generiere Slug aus Titel wenn nicht vorhanden
        $slug = isset($validated['slug']) && !empty($validated['slug'])
            ? $validated['slug']
            : $this->generateSlug($validated['title']);

        // Validiere den generierten/übergebenen Slug
        $slug = Validator::slug($slug);

        // Kritischer Abschnitt: Slug-Eindeutigkeit prüfen und Datei erstellen
        // muss atomar sein, um Race Conditions zu vermeiden
        $lockFile = $this->contentPath . '/.slug-creation.lock';
        $lockHandle = fopen($lockFile, 'c');

        if ($lockHandle === false) {
            throw new StorageException('Konnte Lock-Datei nicht erstellen');
        }

        try {
            // Exklusives Lock für Slug-Generierung und Datei-Erstellung
            if (!flock($lockHandle, LOCK_EX)) {
                throw new StorageException('Konnte Lock nicht erwerben');
            }

            // Stelle sicher, dass Slug eindeutig ist (jetzt thread-safe)
            $slug = $this->ensureUniqueSlug($slug);

            $page = [
                'id' => $this->generateId(),
                'slug' => $slug,
                'title' => $validated['title'],
                'status' => $validated['status'],
                'sections' => $validated['sections'],
                'navigation' => [
                    'main' => $validated['nav_main'],
                    'footer' => $validated['nav_footer'],
                    'order' => $validated['nav_order'],
                    'label' => $validated['nav_label'],
                ],
                'meta' => [
                    'description' => $validated['meta_description'],
                    'keywords' => $validated['meta_keywords'],
                ],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            // Speichere Seite (noch unter Lock)
            $result = $this->savePage($slug, $page);

            // Lock freigeben
            flock($lockHandle, LOCK_UN);

            return $result;
        } finally {
            fclose($lockHandle);
        }
    }

    /**
     * Aktualisiert eine existierende Seite
     *
     * @param string $slug Der Slug der zu aktualisierenden Seite
     * @param array $data Die neuen Seitendaten
     * @return bool True bei Erfolg
     * @throws ValidationException Bei ungültigen Daten
     * @throws NotFoundException Wenn Seite nicht existiert
     * @throws StorageException Bei Speicherfehlern
     */
    public function updatePage(string $slug, array $data): bool
    {
        // Validiere Slug
        $slug = Validator::slug($slug);

        $page = $this->getPage($slug);

        if (!$page) {
            throw NotFoundException::page($slug);
        }

        // Aktualisiere nur erlaubte Felder mit Validierung
        if (isset($data['title'])) {
            $page['title'] = Validator::string($data['title'], minLength: 1, maxLength: 255, allowEmpty: false);
        }
        if (isset($data['status'])) {
            $page['status'] = Validator::enum($data['status'], ['draft', 'published'], 'status');
        }
        if (isset($data['sections'])) {
            $page['sections'] = Validator::array($data['sections'], minItems: 0, maxItems: 100);
        }
        if (isset($data['nav_main'])) {
            $page['navigation']['main'] = Validator::boolean($data['nav_main']);
        }
        if (isset($data['nav_footer'])) {
            $page['navigation']['footer'] = Validator::boolean($data['nav_footer']);
        }
        if (isset($data['nav_order'])) {
            $page['navigation']['order'] = Validator::integer($data['nav_order'], min: 0, max: 9999);
        }
        if (isset($data['nav_label'])) {
            $page['navigation']['label'] = Validator::string($data['nav_label'], maxLength: 100);
        }
        if (isset($data['meta_description'])) {
            $page['meta']['description'] = Validator::string($data['meta_description'], maxLength: 500);
        }
        if (isset($data['meta_keywords'])) {
            $page['meta']['keywords'] = Validator::string($data['meta_keywords'], maxLength: 255);
        }

        $page['updated_at'] = date('Y-m-d H:i:s');

        return $this->savePage($slug, $page);
    }

    /**
     * Löscht eine Seite
     *
     * @param string $slug Der Slug der zu löschenden Seite
     * @return bool True bei Erfolg
     * @throws ValidationException Bei ungültigem Slug
     * @throws NotFoundException Wenn Seite nicht existiert
     * @throws StorageException Bei Löschfehlern
     */
    public function deletePage(string $slug): bool
    {
        // Validiere Slug gegen Path-Traversal
        $slug = Validator::slug($slug);

        $filePath = $this->getPagePath($slug);

        if (!file_exists($filePath)) {
            throw NotFoundException::page($slug);
        }

        $success = @unlink($filePath);

        if (!$success) {
            throw StorageException::cannotDelete($filePath);
        }

        return true;
    }

    /**
     * Holt eine Seite anhand ihres Slugs
     *
     * @param string $slug Der Slug der Seite
     * @return array|null Die Seitendaten oder null wenn nicht gefunden
     * @throws ValidationException Bei ungültigem Slug
     * @throws StorageException Bei Lesefehlern oder ungültigem JSON
     */
    public function getPage(string $slug): ?array
    {
        // Validiere Slug gegen Path-Traversal
        $slug = Validator::slug($slug);

        $filePath = $this->getPagePath($slug);

        if (!file_exists($filePath)) {
            return null;
        }

        $content = @file_get_contents($filePath);

        if ($content === false) {
            throw StorageException::cannotRead($filePath);
        }

        $page = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw StorageException::invalidJson($filePath);
        }

        return is_array($page) ? $page : null;
    }

    /**
     * Holt alle Seiten
     *
     * @return array Array mit allen Seiten
     * @throws StorageException Bei Lesefehlern oder ungültigem JSON
     */
    public function getAllPages(): array
    {
        $pages = [];
        $files = glob($this->contentPath . '/*.json');

        if ($files === false) {
            throw StorageException::cannotRead($this->contentPath);
        }

        foreach ($files as $file) {
            $content = @file_get_contents($file);

            if ($content === false) {
                // Überspringe Dateien die nicht gelesen werden können
                continue;
            }

            $page = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Überspringe ungültige JSON-Dateien
                continue;
            }

            if (is_array($page)) {
                $pages[] = $page;
            }
        }

        // Sortiere nach Navigation-Order
        usort($pages, function($a, $b) {
            return ($a['navigation']['order'] ?? 0) <=> ($b['navigation']['order'] ?? 0);
        });

        return $pages;
    }

    /**
     * Holt veröffentlichte Seiten
     */
    public function getPublishedPages(): array
    {
        $pages = $this->getAllPages();

        return array_filter($pages, function($page) {
            return ($page['status'] ?? 'draft') === 'published';
        });
    }

    /**
     * Holt Seiten für Navigation
     */
    public function getNavigationPages(string $location = 'main'): array
    {
        $pages = $this->getPublishedPages();

        return array_filter($pages, function($page) use ($location) {
            return ($page['navigation'][$location] ?? false) === true;
        });
    }

    /**
     * Speichert eine Seite mit File-Locking
     *
     * @param string $slug Der Slug der Seite
     * @param array $page Die Seitendaten
     * @return bool True bei Erfolg
     * @throws StorageException Bei Speicherfehlern
     */
    private function savePage(string $slug, array $page): bool
    {
        $filePath = $this->getPagePath($slug);
        $content = json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($content === false) {
            throw StorageException::cannotWrite($filePath, 'JSON encoding failed');
        }

        // Öffne Datei mit exklusivem Lock
        $handle = @fopen($filePath, 'c');

        if (!$handle) {
            throw StorageException::cannotWrite($filePath, 'Cannot open file');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw StorageException::cannotWrite($filePath, 'Cannot acquire lock');
            }

            ftruncate($handle, 0);
            $written = fwrite($handle, $content);

            if ($written === false) {
                throw StorageException::cannotWrite($filePath, 'Write operation failed');
            }

            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        return true;
    }

    /**
     * Generiert einen URL-Slug aus einem Titel
     */
    private function generateSlug(string $title): string
    {
        // Umlaute ersetzen
        $slug = str_replace(
            ['ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'],
            ['ae', 'oe', 'ue', 'ss', 'ae', 'oe', 'ue'],
            $title
        );

        // Alles außer Buchstaben, Zahlen und Bindestriche entfernen
        $slug = preg_replace('/[^a-z0-9-]+/i', '-', $slug);
        $slug = strtolower(trim($slug, '-'));

        return $slug;
    }

    /**
     * Stellt sicher, dass ein Slug eindeutig ist
     */
    private function ensureUniqueSlug(string $slug): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while ($this->getPage($slug) !== null) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Generiert eine eindeutige ID
     */
    private function generateId(): string
    {
        return uniqid('page_', true);
    }

    /**
     * Gibt den Dateipfad für eine Seite zurück
     */
    private function getPagePath(string $slug): string
    {
        return $this->contentPath . '/' . $slug . '.json';
    }

    /**
     * Prüft ob eine Seite existiert
     *
     * @param string $slug Der zu prüfende Slug
     * @return bool True wenn Seite existiert
     * @throws ValidationException Bei ungültigem Slug
     */
    public function pageExists(string $slug): bool
    {
        // Validiere Slug gegen Path-Traversal
        $slug = Validator::slug($slug);

        return file_exists($this->getPagePath($slug));
    }
}
