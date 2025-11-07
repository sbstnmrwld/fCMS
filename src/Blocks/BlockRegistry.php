<?php

namespace FCMS\Blocks;

/**
 * Block-Registry
 *
 * Verwaltet alle registrierten Blöcke und ermöglicht automatische Block-Discovery.
 */
class BlockRegistry
{
    private array $blocks = [];
    private string $blocksPath;

    public function __construct(string $blocksPath)
    {
        $this->blocksPath = $blocksPath;
    }

    /**
     * Registriert einen Block
     */
    public function register(string $name, BlockInterface $block): void
    {
        $this->blocks[$name] = $block;
    }

    /**
     * Holt einen registrierten Block
     */
    public function get(string $name): ?BlockInterface
    {
        return $this->blocks[$name] ?? null;
    }

    /**
     * Prüft ob ein Block registriert ist
     */
    public function has(string $name): bool
    {
        return isset($this->blocks[$name]);
    }

    /**
     * Gibt alle registrierten Blöcke zurück
     */
    public function all(): array
    {
        return $this->blocks;
    }

    /**
     * Gibt alle Block-Metadaten zurück (für Block-Auswahl im Editor)
     */
    public function getAllMetadata(): array
    {
        $metadata = [];

        foreach ($this->blocks as $name => $block) {
            $metadata[$name] = $block->getMetadata();
        }

        return $metadata;
    }

    /**
     * Lädt automatisch alle Blöcke aus dem Blocks-Verzeichnis
     */
    public function autoDiscoverBlocks(): void
    {
        $files = glob($this->blocksPath . '/*Block.php');

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);

            if ($className && class_exists($className)) {
                $reflection = new \ReflectionClass($className);

                if ($reflection->implementsInterface(BlockInterface::class) && !$reflection->isAbstract()) {
                    $block = new $className();
                    $metadata = $block->getMetadata();
                    $blockName = $metadata['name'] ?? basename($file, '.php');

                    $this->register($blockName, $block);
                }
            }
        }
    }

    /**
     * Extrahiert Klassennamen aus Datei
     */
    private function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);

        // Finde Namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
            $namespace = $namespaceMatch[1];
        } else {
            return null;
        }

        // Finde Klassenname
        if (preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            $className = $classMatch[1];
        } else {
            return null;
        }

        return $namespace . '\\' . $className;
    }

    /**
     * Rendert einen Block
     */
    public function renderBlock(string $blockName, array $attributes, string $content = ''): string
    {
        $block = $this->get($blockName);

        if (!$block) {
            return '<!-- Block "' . htmlspecialchars($blockName) . '" nicht gefunden -->';
        }

        try {
            return $block->render($attributes, $content);
        } catch (\Exception $e) {
            return '<!-- Fehler beim Rendern von Block "' . htmlspecialchars($blockName) . '": ' .
                   htmlspecialchars($e->getMessage()) . ' -->';
        }
    }
}
