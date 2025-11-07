<?php
/**
 * fCMS Build Script
 * 
 * Erstellt ein produktionsfertiges Release-ZIP ohne Entwicklungs-Abhängigkeiten.
 * 
 * Verwendung: php build/build.php [version]
 */

declare(strict_types=1);

class FCMSBuilder
{
    private string $rootDir;
    private string $buildDir;
    private string $tempDir;
    private string $version;
    
    // Dateien und Verzeichnisse die NICHT ins Release sollen
    private array $excludePatterns = [
        '.git',
        '.gitignore',
        '.vscode',
        '.idea',
        'node_modules',
        'tests',
        'build',
        'releases',
        'composer.json',
        'composer.lock',
        'phpunit.xml',
        '.editorconfig',
        '.env',
        '.env.example',
        'README.dev.md',
    ];
    
    public function __construct(string $version = '1.0.0')
    {
        $this->rootDir = dirname(__DIR__);
        $this->buildDir = $this->rootDir . '/build';
        $this->tempDir = $this->buildDir . '/temp';
        $this->version = $version;
    }
    
    public function build(): void
    {
        echo "fCMS Release Builder v{$this->version}\n";
        echo str_repeat('=', 50) . "\n\n";
        
        // 1. Vorbereitung
        echo "[1/7] Vorbereitung...\n";
        $this->prepare();
        
        // 2. Composer-Dependencies optimiert installieren
        echo "[2/7] Installiere Produktions-Dependencies...\n";
        $this->installProductionDependencies();
        
        // 3. Dateien kopieren
        echo "[3/7] Kopiere Dateien...\n";
        $this->copyFiles();
        
        // 4. Vendor-Verzeichnis optimieren
        echo "[4/7] Optimiere Vendor-Verzeichnis...\n";
        $this->optimizeVendor();
        
        // 5. Konfigurationsdatei vorbereiten
        echo "[5/7] Bereite Konfiguration vor...\n";
        $this->prepareConfig();
        
        // 6. ZIP erstellen
        echo "[6/7] Erstelle Release-ZIP...\n";
        $zipFile = $this->createZip();
        
        // 7. Aufräumen
        echo "[7/7] Räume auf...\n";
        $this->cleanup();
        
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "✓ Build erfolgreich!\n";
        echo "Release-Datei: {$zipFile}\n";
        echo "Größe: " . $this->formatBytes(filesize($zipFile)) . "\n";
        echo "MD5: " . md5_file($zipFile) . "\n";
        echo str_repeat('=', 50) . "\n";
    }
    
    private function prepare(): void
    {
        // Erstelle Build-Verzeichnisse
        if (!is_dir($this->buildDir)) {
            mkdir($this->buildDir, 0755, true);
        }
        
        $releasesDir = $this->rootDir . '/releases';
        if (!is_dir($releasesDir)) {
            mkdir($releasesDir, 0755, true);
        }
        
        // Lösche altes Temp-Verzeichnis falls vorhanden
        if (is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
        }
        
        mkdir($this->tempDir, 0755, true);
    }
    
    private function installProductionDependencies(): void
    {
        $cmd = "cd {$this->rootDir} && composer install --no-dev --optimize-autoloader --no-interaction 2>&1";
        exec($cmd, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new RuntimeException("Composer install fehlgeschlagen:\n" . implode("\n", $output));
        }
    }
    
    private function copyFiles(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($this->rootDir) + 1);
            
            // Prüfe ob Datei ausgeschlossen werden soll
            if ($this->shouldExclude($relativePath)) {
                continue;
            }
            
            $targetPath = $this->tempDir . '/' . $relativePath;
            
            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                copy($item->getPathname(), $targetPath);
            }
        }
    }
    
    private function shouldExclude(string $path): bool
    {
        foreach ($this->excludePatterns as $pattern) {
            if (strpos($path, $pattern) === 0) {
                return true;
            }
        }
        
        // Exclude log files
        if (preg_match('/\.log$/', $path)) {
            return true;
        }
        
        // Exclude user content (wird beim Deployment erstellt)
        if (preg_match('#^content/pages/.*\.json$#', $path)) {
            return true;
        }
        
        return false;
    }
    
    private function optimizeVendor(): void
    {
        $vendorDir = $this->tempDir . '/vendor';
        
        if (!is_dir($vendorDir)) {
            return;
        }
        
        // Lösche unnötige Dateien aus Vendor
        $unnecessaryPatterns = [
            '*/tests/*',
            '*/Tests/*',
            '*/test/*',
            '*/docs/*',
            '*/doc/*',
            '*/examples/*',
            '*/example/*',
            '*/.github/*',
            '*/README*',
            '*/CHANGELOG*',
            '*/LICENSE*',
            '*/.gitignore',
            '*/.travis.yml',
            '*/phpunit.xml*',
            '*/composer.json',
        ];
        
        foreach ($unnecessaryPatterns as $pattern) {
            $files = glob($vendorDir . '/' . $pattern, GLOB_BRACE);
            foreach ($files as $file) {
                if (is_dir($file)) {
                    $this->deleteDirectory($file);
                } elseif (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
    
    private function prepareConfig(): void
    {
        $configExample = $this->tempDir . '/config/config.example.php';
        $config = $this->tempDir . '/config/config.php';
        
        // Kopiere Beispiel-Konfiguration als Standard-Config
        if (file_exists($configExample) && !file_exists($config)) {
            copy($configExample, $config);
        }
        
        // Erstelle README für Erstkonfiguration
        $setupReadme = $this->tempDir . '/ERSTE-SCHRITTE.txt';
        file_put_contents($setupReadme, $this->getSetupInstructions());
    }
    
    private function getSetupInstructions(): string
    {
        return <<<EOT
fCMS - Erste Schritte nach der Installation
============================================

1. Entpacken Sie diese ZIP-Datei auf Ihrem Computer

2. Öffnen Sie die Datei config/config.php und passen Sie folgende Einstellungen an:
   - site.name: Name Ihrer Website
   - site.url: URL Ihrer Website
   - admin.username: Ihr Admin-Benutzername
   - admin.password: Generieren Sie ein neues Passwort-Hash
   
   Passwort-Hash generieren:
   Öffnen Sie die Datei generate-password.php im Browser nach dem Upload
   oder führen Sie folgenden PHP-Code aus:
   
   <?php echo password_hash('IhrPasswort', PASSWORD_DEFAULT); ?>

3. Laden Sie alle Dateien per FTP/SFTP auf Ihren Webserver hoch
   - Empfohlenes Zielverzeichnis: public_html/ oder httpdocs/

4. Stellen Sie sicher, dass folgende Verzeichnisse beschreibbar sind (chmod 755 oder 775):
   - content/
   - content/pages/
   - content/media/
   - logs/

5. Rufen Sie Ihre Website auf: https://ihre-domain.de

6. Melden Sie sich im Admin-Bereich an: https://ihre-domain.de/admin

7. WICHTIG: Löschen Sie nach dem ersten Login die Datei generate-password.php

Bei Problemen lesen Sie bitte die vollständige Dokumentation in README.md

Viel Erfolg mit fCMS!
EOT;
    }
    
    private function createZip(): string
    {
        $zipFileName = "fCMS-v{$this->version}.zip";
        $zipPath = $this->rootDir . '/releases/' . $zipFileName;
        
        // Lösche alte ZIP falls vorhanden
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
        
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException("Kann ZIP-Datei nicht erstellen: {$zipPath}");
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($this->tempDir) + 1);
            
            if ($item->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($item->getPathname(), $relativePath);
            }
        }
        
        $zip->close();
        
        return $zipPath;
    }
    
    private function cleanup(): void
    {
        if (is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
        }
        
        // Entwicklungs-Dependencies wieder installieren
        $cmd = "cd {$this->rootDir} && composer install 2>&1";
        exec($cmd);
    }
    
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $items = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Build ausführen
try {
    $version = $argv[1] ?? '1.0.0';
    $builder = new FCMSBuilder($version);
    $builder->build();
} catch (Exception $e) {
    echo "\n✗ Build fehlgeschlagen: " . $e->getMessage() . "\n";
    exit(1);
}
