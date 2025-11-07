<?php

namespace FCMS\Core;

/**
 * Abstract Module
 *
 * Basis-Klasse für alle Module mit Standard-Implementierungen
 */
abstract class AbstractModule implements ModuleInterface
{
    protected string $modulePath = '';
    protected array $config = [];

    public function __construct(string $modulePath = '')
    {
        // Standardwert setzen, falls kein Pfad übergeben wird
        $this->modulePath = $modulePath ?: __DIR__;
        $this->loadConfig();
    }

    /**
     * Lädt die module.json Konfiguration
     */
    protected function loadConfig(): void
    {
        $configFile = $this->modulePath . '/module.json';

        if (file_exists($configFile)) {
            $json = file_get_contents($configFile);
            $this->config = json_decode($json, true) ?? [];
        }
    }

    public function getName(): string
    {
        return $this->config['name'] ?? basename($this->modulePath);
    }

    public function getVersion(): string
    {
        return $this->config['version'] ?? '000000000000-dev';
    }

    public function getTitle(): string
    {
        return $this->config['title'] ?? $this->getName();
    }

    public function getDescription(): string
    {
        return $this->config['description'] ?? '';
    }

    public function getAuthor(): string
    {
        return $this->config['author'] ?? 'Unknown';
    }

    public function getRequiredFcmsVersion(): string
    {
        return $this->config['requires']['fcms'] ?? '000000000000-dev';
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function isInstalled(): bool
    {
        // Standard: Modul ist installiert wenn Verzeichnis existiert
        return is_dir($this->modulePath);
    }

    public function activate(): void
    {
        // Standard-Implementierung: Nichts tun
        // Module können diese Methode überschreiben
    }

    public function deactivate(): void
    {
        // Standard-Implementierung: Nichts tun
        // Module können diese Methode überschreiben
    }

    public function install(): bool
    {
        // Standard-Implementierung: Bereits installiert
        return true;
    }

    public function uninstall(): bool
    {
        // Standard-Implementierung: Nichts tun
        // Module sollten diese Methode überschreiben wenn sie Daten löschen müssen
        return true;
    }

    /**
     * Gibt den Pfad zum Modul zurück
     */
    public function getPath(): string
    {
        return $this->modulePath;
    }

    /**
     * Gibt den Pfad zu einer Modul-Datei zurück
     */
    protected function getFilePath(string $relativePath): string
    {
        return $this->modulePath . '/' . ltrim($relativePath, '/');
    }

    /**
     * Lädt eine View-Datei
     */
    protected function loadView(string $viewName, array $data = []): string
    {
        $viewFile = $this->getFilePath('views/' . $viewName . '.php');

        if (!file_exists($viewFile)) {
            return '<!-- View "' . htmlspecialchars($viewName) . '" nicht gefunden -->';
        }

        extract($data);
        ob_start();
        include $viewFile;
        return ob_get_clean();
    }

    /**
     * Gibt Admin-Assets zurück (CSS/JS die im Admin-Bereich geladen werden sollen)
     * Module können diese Methode überschreiben um eigene Assets zu registrieren
     *
     * @return array ['css' => ['path1', 'path2'], 'js' => ['path1', 'path2']]
     */
    public function getAdminAssets(): array
    {
        return [
            'css' => [],
            'js' => []
        ];
    }
}
