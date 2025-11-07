<?php

namespace FCMS\Core;

/**
 * Admin-Asset-Manager
 * 
 * Verwaltet Assets ausschließlich für den Admin-Bereich.
 * Alle Assets liegen in /admin/assets/ und sind vom Frontend getrennt.
 */
class AdminAssetManager
{
    private string $basePath;
    private string $baseUrl;

    public function __construct(string $adminPath, string $baseUrl = '')
    {
        $this->basePath = $adminPath . '/assets';
        $this->baseUrl = rtrim($baseUrl, '/') . '/admin/assets';
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
