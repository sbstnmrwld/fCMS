<?php

namespace FCMS\Core;

/**
 * Language-Manager
 *
 * Verwaltet Mehrsprachigkeit durch JSON-basierte Sprachdateien.
 * Standard-Sprache ist Deutsch, weitere Sprachen können einfach hinzugefügt werden.
 */
class LanguageManager
{
    private string $languagesPath;
    private string $currentLanguage;
    private array $translations = [];
    private array $cache = [];
    private string $fallbackLanguage = 'de';

    public function __construct(string $languagesPath, string $defaultLanguage = 'de')
    {
        $this->languagesPath = $languagesPath;
        $this->currentLanguage = $defaultLanguage;
        $this->loadTranslations($defaultLanguage);
    }

    /**
     * Lädt Übersetzungen für eine Sprache
     */
    private function loadTranslations(string $language): void
    {
        // Prüfe Cache
        if (isset($this->cache[$language])) {
            $this->translations = $this->cache[$language];
            return;
        }

        $filePath = $this->languagesPath . '/' . $language . '.json';

        if (!file_exists($filePath)) {
            // Fallback zu Deutsch
            if ($language !== $this->fallbackLanguage) {
                $this->loadTranslations($this->fallbackLanguage);
                return;
            }
            $this->translations = [];
            return;
        }

        $content = file_get_contents($filePath);
        $translations = json_decode($content, true);

        if (!is_array($translations)) {
            $translations = [];
        }

        $this->translations = $translations;
        $this->cache[$language] = $translations;
    }

    /**
     * Übersetzt einen Text-Key
     */
    public function translate(string $key, array $params = []): string
    {
        $translation = $this->getNestedValue($this->translations, $key);

        // Fallback zu Deutsch wenn Übersetzung fehlt
        if ($translation === null && $this->currentLanguage !== $this->fallbackLanguage) {
            if (!isset($this->cache[$this->fallbackLanguage])) {
                $this->loadTranslations($this->fallbackLanguage);
            }
            $translation = $this->getNestedValue($this->cache[$this->fallbackLanguage], $key);
        }

        // Fallback zum Key selbst
        if ($translation === null) {
            $translation = $key;
        }

        // Ersetze Platzhalter
        foreach ($params as $paramKey => $value) {
            $translation = str_replace('{' . $paramKey . '}', $value, $translation);
        }

        return $translation;
    }

    /**
     * Shortcut für translate()
     */
    public function t(string $key, array $params = []): string
    {
        return $this->translate($key, $params);
    }

    /**
     * Holt verschachtelten Wert aus Array mit Punkt-Notation
     */
    private function getNestedValue(array $array, string $key)
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Setzt die aktuelle Sprache
     */
    public function setLanguage(string $language): void
    {
        $this->currentLanguage = $language;
        $this->loadTranslations($language);
    }

    /**
     * Gibt die aktuelle Sprache zurück
     */
    public function getCurrentLanguage(): string
    {
        return $this->currentLanguage;
    }

    /**
     * Gibt Liste aller verfügbaren Sprachen zurück
     */
    public function getAvailableLanguages(): array
    {
        $languages = [];
        $files = glob($this->languagesPath . '/*.json');

        foreach ($files as $file) {
            $language = basename($file, '.json');
            $languages[] = $language;
        }

        return $languages;
    }

    /**
     * Prüft ob eine Sprache verfügbar ist
     */
    public function hasLanguage(string $language): bool
    {
        return file_exists($this->languagesPath . '/' . $language . '.json');
    }
}
