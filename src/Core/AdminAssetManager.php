<?php

namespace FCMS\Core;

/**
 * Admin-Asset-Manager
 *
 * Verwaltet Assets ausschließlich für den Admin-Bereich.
 * Alle Assets liegen in /public/admin/assets/ und werden direkt vom Webserver ausgeliefert.
 */
class AdminAssetManager
{
    private string $basePath;
    private string $baseUrl;
    private bool $cacheBusting;

    public function __construct(string $adminPath, string $baseUrl = '', bool $cacheBusting = true)
    {
        $this->basePath = $adminPath . '/assets';
        // Wenn kein baseUrl angegeben, verwende relative Pfade
        if (empty($baseUrl)) {
            $this->baseUrl = '/admin/assets';
        } else {
            $this->baseUrl = rtrim($baseUrl, '/') . '/admin/assets';
        }
        $this->cacheBusting = $cacheBusting;
    }

    /**
     * Fügt Cache-Busting-Parameter hinzu
     */
    private function addCacheBuster(string $url, string $filePath): string
    {
        if (!$this->cacheBusting) {
            return $url;
        }

        // Verwende filemtime als Version
        $fullPath = $this->basePath . '/' . ltrim($filePath, '/');
        if (file_exists($fullPath)) {
            $mtime = filemtime($fullPath);
            return $url . '?v=' . $mtime;
        }

        return $url;
    }

    /**
     * Gibt URL zu einem CSS-Asset zurück
     */
    public function css(string $file): string
    {
        $url = $this->baseUrl . '/css/' . ltrim($file, '/');
        return $this->addCacheBuster($url, 'css/' . ltrim($file, '/'));
    }

    /**
     * Gibt URL zu einem JavaScript-Asset zurück
     */
    public function js(string $file): string
    {
        $url = $this->baseUrl . '/js/' . ltrim($file, '/');
        return $this->addCacheBuster($url, 'js/' . ltrim($file, '/'));
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
     * Gibt URL zu Bootstrap CSS zurück
     */
    public function bootstrapCss(): string
    {
        return $this->baseUrl . '/bootstrap/css/bootstrap.min.css';
    }

    /**
     * Gibt URL zu Bootstrap JS zurück
     */
    public function bootstrapJs(): string
    {
        return $this->baseUrl . '/bootstrap/js/bootstrap.bundle.min.js';
    }

    /**
     * Gibt URL zu Bootstrap Icons CSS zurück
     */
    public function bootstrapIcons(): string
    {
        return $this->baseUrl . '/bootstrap-icons/bootstrap-icons.min.css';
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
        $fullPath = $this->basePath . '/' . ltrim($path, '/');
        return file_exists($fullPath);
    }

    /**
     * Gibt den physischen Pfad zu einem Asset zurück
     */
    public function path(string $file): string
    {
        return $this->basePath . '/' . ltrim($file, '/');
    }
}
