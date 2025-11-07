<?php

namespace FCMS\Core;

/**
 * Theme-Asset-Manager
 *
 * Verwaltet Assets ausschließlich für das aktive Frontend-Theme.
 * Jedes Theme hat seine eigene Asset-Struktur und ist vollständig eigenständig.
 * Keine gemeinsamen Assets mit dem Admin-Bereich oder anderen Themes.
 */
class ThemeAssetManager
{
    private string $themesPath;
    private string $activeTheme;
    private string $baseUrl;

    public function __construct(string $themesPath, string $activeTheme, string $baseUrl = '')
    {
        $this->themesPath = $themesPath;
        $this->activeTheme = $activeTheme;
        $this->baseUrl = rtrim($baseUrl, '/') . '/themes/' . $activeTheme . '/assets';
    }

    /**
     * Gibt URL zu einem CSS-Asset zurück
     */
    public function css(string $file): string
    {
        return $this->baseUrl . '/css/' . ltrim($file, '/');
    }

    /**
     * Gibt URL zu einem JavaScript-Asset zurück
     */
    public function js(string $file): string
    {
        return $this->baseUrl . '/js/' . ltrim($file, '/');
    }

    /**
     * Gibt URL zu einem Bild-Asset zurück
     */
    public function image(string $file): string
    {
        return $this->baseUrl . '/images/' . ltrim($file, '/');
    }

    /**
     * Gibt URL zu einem Font-Asset zurück
     */
    public function font(string $file): string
    {
        return $this->baseUrl . '/fonts/' . ltrim($file, '/');
    }

    /**
     * Gibt URL zu Bootstrap CSS zurück (falls Theme Bootstrap verwendet)
     */
    public function bootstrapCss(): string
    {
        return $this->baseUrl . '/bootstrap/css/bootstrap.min.css';
    }

    /**
     * Gibt URL zu Bootstrap JS zurück (falls Theme Bootstrap verwendet)
     */
    public function bootstrapJs(): string
    {
        return $this->baseUrl . '/bootstrap/js/bootstrap.bundle.min.js';
    }

    /**
     * Gibt URL zu einem beliebigen Asset zurück
     */
    public function asset(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Prüft ob ein Asset existiert
     */
    public function exists(string $path): bool
    {
        $fullPath = $this->getAssetPath($path);
        return file_exists($fullPath);
    }

    /**
     * Gibt den physischen Pfad zu einem Asset zurück
     */
    public function path(string $file): string
    {
        return $this->getAssetPath($file);
    }

    /**
     * Gibt den physischen Asset-Pfad zurück
     */
    private function getAssetPath(string $file): string
    {
        return $this->themesPath . '/' . $this->activeTheme . '/assets/' . ltrim($file, '/');
    }

    /**
     * Gibt den Template-Pfad des aktiven Themes zurück
     */
    public function getTemplatePath(): string
    {
        return $this->themesPath . '/' . $this->activeTheme . '/templates';
    }

    /**
     * Gibt den Namen des aktiven Themes zurück
     */
    public function getActiveTheme(): string
    {
        return $this->activeTheme;
    }

    /**
     * Wechselt das aktive Theme
     */
    public function switchTheme(string $themeName): void
    {
        $this->activeTheme = $themeName;
        $this->baseUrl = rtrim($this->baseUrl, '/themes/' . $this->activeTheme . '/assets')
                       . '/themes/' . $themeName . '/assets';
    }
}
