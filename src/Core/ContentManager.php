<?php

namespace FCMS\Core;

use Symfony\Component\Filesystem\Filesystem;

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
     */
    public function createPage(array $data): bool
    {
        $slug = $this->generateSlug($data['title'] ?? 'neue-seite');

        // Stelle sicher, dass Slug eindeutig ist
        $slug = $this->ensureUniqueSlug($slug);

        $page = [
            'id' => $this->generateId(),
            'slug' => $slug,
            'title' => $data['title'] ?? 'Neue Seite',
            'status' => $data['status'] ?? 'draft',
            'sections' => $data['sections'] ?? [],
            'navigation' => [
                'main' => $data['nav_main'] ?? false,
                'footer' => $data['nav_footer'] ?? false,
                'order' => $data['nav_order'] ?? 0,
                'label' => $data['nav_label'] ?? '',
            ],
            'meta' => [
                'description' => $data['meta_description'] ?? '',
                'keywords' => $data['meta_keywords'] ?? '',
            ],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return $this->savePage($slug, $page);
    }

    /**
     * Aktualisiert eine existierende Seite
     */
    public function updatePage(string $slug, array $data): bool
    {
        $page = $this->getPage($slug);

        if (!$page) {
            return false;
        }

        // Aktualisiere nur erlaubte Felder
        if (isset($data['title'])) {
            $page['title'] = $data['title'];
        }
        if (isset($data['status'])) {
            $page['status'] = $data['status'];
        }
        if (isset($data['sections'])) {
            $page['sections'] = $data['sections'];
        }
        if (isset($data['nav_main'])) {
            $page['navigation']['main'] = (bool)$data['nav_main'];
        }
        if (isset($data['nav_footer'])) {
            $page['navigation']['footer'] = (bool)$data['nav_footer'];
        }
        if (isset($data['nav_order'])) {
            $page['navigation']['order'] = (int)$data['nav_order'];
        }
        if (isset($data['nav_label'])) {
            $page['navigation']['label'] = $data['nav_label'];
        }
        if (isset($data['meta_description'])) {
            $page['meta']['description'] = $data['meta_description'];
        }
        if (isset($data['meta_keywords'])) {
            $page['meta']['keywords'] = $data['meta_keywords'];
        }

        $page['updated_at'] = date('Y-m-d H:i:s');

        return $this->savePage($slug, $page);
    }

    /**
     * Löscht eine Seite
     */
    public function deletePage(string $slug): bool
    {
        $filePath = $this->getPagePath($slug);

        if (!file_exists($filePath)) {
            return false;
        }

        return unlink($filePath);
    }

    /**
     * Holt eine Seite anhand ihres Slugs
     */
    public function getPage(string $slug): ?array
    {
        $filePath = $this->getPagePath($slug);

        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        $page = json_decode($content, true);

        return is_array($page) ? $page : null;
    }

    /**
     * Holt alle Seiten
     */
    public function getAllPages(): array
    {
        $pages = [];
        $files = glob($this->contentPath . '/*.json');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $page = json_decode($content, true);

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
     */
    private function savePage(string $slug, array $page): bool
    {
        $filePath = $this->getPagePath($slug);
        $content = json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Öffne Datei mit exklusivem Lock
        $handle = fopen($filePath, 'c');

        if (!$handle) {
            return false;
        }

        if (flock($handle, LOCK_EX)) {
            ftruncate($handle, 0);
            fwrite($handle, $content);
            fflush($handle);
            flock($handle, LOCK_UN);
        }

        fclose($handle);
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
     */
    public function pageExists(string $slug): bool
    {
        return file_exists($this->getPagePath($slug));
    }
}
