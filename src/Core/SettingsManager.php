<?php

namespace FCMS\Core;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Settings-Manager
 *
 * Verwaltet System-Einstellungen in einer JSON-Datei.
 */
class SettingsManager
{
    private string $settingsPath;
    private Filesystem $filesystem;
    private array $settings = [];

    public function __construct(string $contentPath)
    {
        $this->settingsPath = $contentPath . '/settings.json';
        $this->filesystem = new Filesystem();
        $this->loadSettings();
    }

    /**
     * Lädt die Einstellungen aus der Datei
     */
    private function loadSettings(): void
    {
        if (file_exists($this->settingsPath)) {
            $content = file_get_contents($this->settingsPath);
            $this->settings = json_decode($content, true) ?? [];
        } else {
            $this->settings = $this->getDefaultSettings();
            $this->saveSettings();
        }
    }

    /**
     * Gibt die Standard-Einstellungen zurück
     */
    private function getDefaultSettings(): array
    {
        return [
            'site' => [
                'name' => 'Meine Website',
                'tagline' => '',
                'language' => 'de',
                'timezone' => 'Europe/Berlin',
            ],
            'seo' => [
                'meta_description' => '',
                'meta_keywords' => '',
                'robots' => 'index, follow',
            ],
            'theme' => [
                'active' => 'default',
            ],
            'maintenance' => [
                'enabled' => false,
                'message' => 'Die Website befindet sich derzeit im Wartungsmodus.',
            ],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Speichert die Einstellungen
     */
    private function saveSettings(): bool
    {
        $this->settings['updated_at'] = date('Y-m-d H:i:s');
        $content = json_encode($this->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Öffne Datei mit exklusivem Lock
        $handle = fopen($this->settingsPath, 'c');

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
     * Holt eine Einstellung
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->settings;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Setzt eine Einstellung
     */
    public function set(string $key, $value): bool
    {
        $keys = explode('.', $key);
        $settings = &$this->settings;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $settings[$k] = $value;
            } else {
                if (!isset($settings[$k]) || !is_array($settings[$k])) {
                    $settings[$k] = [];
                }
                $settings = &$settings[$k];
            }
        }

        return $this->saveSettings();
    }

    /**
     * Aktualisiert mehrere Einstellungen auf einmal
     */
    public function update(array $data): bool
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }

        return true;
    }

    /**
     * Gibt alle Einstellungen zurück
     */
    public function all(): array
    {
        return $this->settings;
    }

    /**
     * Setzt die Einstellungen auf Standard zurück
     */
    public function reset(): bool
    {
        $this->settings = $this->getDefaultSettings();
        return $this->saveSettings();
    }
}
