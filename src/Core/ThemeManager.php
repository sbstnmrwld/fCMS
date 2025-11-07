<?php

namespace FCMS\Core;

/**
 * Theme-Manager
 * 
 * Verwaltet Themes und stellt Template-Rendering-Funktionalität bereit.
 */
class ThemeManager
{
    private string $themesPath;
    private string $activeTheme;
    private ThemeAssetManager $assetManager;
    private LanguageManager $language;

    public function __construct(
        string $themesPath, 
        string $activeTheme, 
        ThemeAssetManager $assetManager,
        LanguageManager $language
    ) {
        $this->themesPath = $themesPath;
        $this->activeTheme = $activeTheme;
        $this->assetManager = $assetManager;
        $this->language = $language;
    }

    /**
     * Rendert ein Template
     */
    public function render(string $template, array $data = []): string
    {
        $templatePath = $this->getTemplatePath($template);

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template '$template' nicht gefunden in Theme '{$this->activeTheme}'");
        }

        // Extrahiere Variablen
        extract($data);

        // Asset-Manager und Language-Manager verfügbar machen
        $assets = $this->assetManager;
        $lang = $this->language;

        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    /**
     * Gibt den Pfad zu einem Template zurück
     */
    private function getTemplatePath(string $template): string
    {
        $template = str_replace('.php', '', $template);
        return $this->themesPath . '/' . $this->activeTheme . '/templates/' . $template . '.php';
    }

    /**
     * Holt alle verfügbaren Themes
     */
    public function getAvailableThemes(): array
    {
        $themes = [];
        $dirs = glob($this->themesPath . '/*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $themeName = basename($dir);
            $infoFile = $dir . '/theme.json';

            if (file_exists($infoFile)) {
                $info = json_decode(file_get_contents($infoFile), true);
                $themes[$themeName] = $info ?? ['name' => $themeName];
            } else {
                $themes[$themeName] = ['name' => $themeName];
            }
        }

        return $themes;
    }

    /**
     * Gibt Informationen zum aktiven Theme zurück
     */
    public function getThemeInfo(): array
    {
        $infoFile = $this->themesPath . '/' . $this->activeTheme . '/theme.json';

        if (file_exists($infoFile)) {
            return json_decode(file_get_contents($infoFile), true) ?? [];
        }

        return ['name' => $this->activeTheme];
    }

    /**
     * Wechselt das aktive Theme
     */
    public function switchTheme(string $themeName): bool
    {
        $themePath = $this->themesPath . '/' . $themeName;

        if (!is_dir($themePath)) {
            return false;
        }

        $this->activeTheme = $themeName;
        $this->assetManager->switchTheme($themeName);

        return true;
    }

    /**
     * Gibt den Namen des aktiven Themes zurück
     */
    public function getActiveTheme(): string
    {
        return $this->activeTheme;
    }
}
