<?php

namespace FCMS\Core;

/**
 * Module Manager
 *
 * Verwaltet alle fCMS-Module: Laden, Aktivieren, Deaktivieren
 */
class ModuleManager
{
    private string $modulesPath;
    private string $dataPath;
    private array $modules = [];
    private array $activeModules = [];

    public function __construct(string $modulesPath, string $dataPath)
    {
        $this->modulesPath = $modulesPath;
        $this->dataPath = $dataPath;
        $this->loadActiveModules();
    }

    /**
     * Entdeckt alle verfügbaren Module
     */
    public function discoverModules(): void
    {
        if (!is_dir($this->modulesPath)) {
            mkdir($this->modulesPath, 0755, true);
            return;
        }

        $moduleDirs = glob($this->modulesPath . '/*', GLOB_ONLYDIR);

        foreach ($moduleDirs as $moduleDir) {
            $moduleName = basename($moduleDir);
            $moduleClass = $this->findModuleClass($moduleDir);

            if ($moduleClass && class_exists($moduleClass)) {
                try {
                    $module = new $moduleClass($moduleDir);
                    if ($module instanceof ModuleInterface) {
                        $this->modules[$moduleName] = $module;
                    }
                } catch (\Exception $e) {
                    error_log("Fehler beim Laden von Modul $moduleName: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Findet die Hauptklasse eines Moduls
     */
    private function findModuleClass(string $moduleDir): ?string
    {
        $moduleName = basename($moduleDir);

        // Konvention: ModulnameModule.php (z.B. FormBuilderModule.php)
        $className = $this->toPascalCase($moduleName) . 'Module';
        $classFile = $moduleDir . '/' . $className . '.php';

        if (file_exists($classFile)) {
            require_once $classFile;
            return 'FCMS\\Modules\\' . $className;
        }

        return null;
    }

    /**
     * Konvertiert kebab-case zu PascalCase
     */
    private function toPascalCase(string $string): string
    {
        return str_replace('-', '', ucwords($string, '-'));
    }

    /**
     * Gibt alle entdeckten Module zurück
     */
    public function getAllModules(): array
    {
        return $this->modules;
    }

    /**
     * Gibt ein bestimmtes Modul zurück
     */
    public function getModule(string $name): ?ModuleInterface
    {
        return $this->modules[$name] ?? null;
    }

    /**
     * Prüft ob ein Modul aktiv ist
     */
    public function isActive(string $name): bool
    {
        return in_array($name, $this->activeModules);
    }

    /**
     * Aktiviert ein Modul
     */
    public function activate(string $name): bool
    {
        $module = $this->getModule($name);

        if (!$module) {
            return false;
        }

        if ($this->isActive($name)) {
            return true; // Bereits aktiv
        }

        try {
            $module->activate();
            $this->activeModules[] = $name;
            $this->saveActiveModules();
            return true;
        } catch (\Exception $e) {
            error_log("Fehler beim Aktivieren von Modul $name: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deaktiviert ein Modul
     */
    public function deactivate(string $name): bool
    {
        $module = $this->getModule($name);

        if (!$module) {
            return false;
        }

        if (!$this->isActive($name)) {
            return true; // Bereits deaktiviert
        }

        try {
            $module->deactivate();
            $this->activeModules = array_values(array_diff($this->activeModules, [$name]));
            $this->saveActiveModules();
            return true;
        } catch (\Exception $e) {
            error_log("Fehler beim Deaktivieren von Modul $name: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Bootet alle aktiven Module
     */
    public function bootActiveModules(object $app, object $container): void
    {
        foreach ($this->activeModules as $moduleName) {
            $module = $this->getModule($moduleName);

            if ($module) {
                try {
                    $module->boot($app, $container);
                } catch (\Exception $e) {
                    error_log("Fehler beim Booten von Modul $moduleName: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Gibt Instanzen aller aktiven Module zurück
     */
    public function getActiveModuleInstances(object $app, object $container): array
    {
        $instances = [];

        foreach ($this->activeModules as $moduleName) {
            $module = $this->getModule($moduleName);

            if ($module) {
                $instances[$moduleName] = $module;
            }
        }

        return $instances;
    }

    /**
     * Gibt die Namen aller aktiven Module zurück
     */
    public function getActiveModules(): array
    {
        return $this->activeModules;
    }

    /**
     * Lädt die Liste aktiver Module
     */
    private function loadActiveModules(): void
    {
        $file = $this->dataPath . '/active-modules.json';

        if (file_exists($file)) {
            $json = file_get_contents($file);
            $this->activeModules = json_decode($json, true) ?? [];
        }
    }

    /**
     * Speichert die Liste aktiver Module
     */
    private function saveActiveModules(): void
    {
        $file = $this->dataPath . '/active-modules.json';

        if (!is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0755, true);
        }

        file_put_contents($file, json_encode($this->activeModules, JSON_PRETTY_PRINT));
    }

    /**
     * Gibt Modul-Metadaten zurück (für Admin-UI)
     */
    public function getModuleInfo(string $name): array
    {
        $module = $this->getModule($name);

        if (!$module) {
            return [];
        }

        return [
            'name' => $module->getName(),
            'version' => $module->getVersion(),
            'title' => $module->getTitle(),
            'description' => $module->getDescription(),
            'author' => $module->getAuthor(),
            'required_fcms_version' => $module->getRequiredFcmsVersion(),
            'is_active' => $this->isActive($name),
            'is_installed' => $module->isInstalled(),
        ];
    }

    /**
     * Gibt Infos zu allen Modulen zurück
     */
    public function getAllModuleInfo(): array
    {
        $info = [];

        foreach ($this->modules as $name => $module) {
            $info[$name] = $this->getModuleInfo($name);
        }

        return $info;
    }
}
