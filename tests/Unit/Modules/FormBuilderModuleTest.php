<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Modules;

use FCMS\Modules\FormBuilderModule;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response as SlimResponse;

/**
 * Tests für FormBuilderModule
 */
class FormBuilderModuleTest extends TestCase
{
    private string $tempDir;
    private string $formsPath;
    private string $submissionsPath;
    private string $logsPath;
    private FormBuilderModule $module;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();

        // Temporäre Verzeichnisse erstellen
        $this->tempDir = sys_get_temp_dir() . '/fcms_test_' . uniqid();
        $this->formsPath = $this->tempDir . '/forms';
        $this->submissionsPath = $this->tempDir . '/submissions';
        $this->logsPath = $this->tempDir . '/logs';

        mkdir($this->formsPath, 0755, true);
        mkdir($this->submissionsPath, 0755, true);
        mkdir($this->logsPath . '/mails', 0755, true);

        // Test-Konfiguration
        $this->config = [
            'paths' => [
                'content' => $this->tempDir,
                'logs' => $this->logsPath,
            ],
        ];

        // Lade FormBuilderModule
        require_once __DIR__ . '/../../../modules/form-builder/FormBuilderModule.php';
        require_once __DIR__ . '/../../../src/Core/AbstractModule.php';

        // Erstelle Modul-Instanz
        $this->module = new \FCMS\Modules\FormBuilderModule($this->tempDir . '/modules/form-builder');

        // Setze formsPath via Reflection, um config.php-Pfad zu überschreiben
        $reflection = new \ReflectionClass($this->module);
        $property = $reflection->getProperty('formsPath');
        $property->setAccessible(true);
        $property->setValue($this->module, $this->formsPath);
    }

    protected function tearDown(): void
    {
        // Räume temporäre Verzeichnisse auf
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testEmailNotificationLogging(): void
    {
        // Test-Formular-Daten
        $formData = [
            'id' => 'test_email_form',
            'title' => 'Test Formular',
            'description' => 'Test Beschreibung',
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'test_field',
                    'label' => 'Test Feld',
                    'required' => false,
                ],
            ],
            'settings' => [
                'email_notification' => true,
                'notification_email' => 'test@example.com',
            ],
        ];

        $submissionData = [
            'test_field' => 'Test Wert',
        ];

        $submissionId = 'sub_test_123';

        // Verwende Reflection um die private sendEmailNotification Methode zu testen
        $reflection = new \ReflectionClass($this->module);
        $method = $reflection->getMethod('sendEmailNotification');
        $method->setAccessible(true);

        // E-Mail senden (wird geloggt) - mit Mail-Log-Pfad für Tests
        $result = $method->invoke(
            $this->module,
            $formData,
            $submissionData,
            $submissionId,
            'test@example.com',
            $this->logsPath . '/mails'  // Expliziter Pfad für Tests
        );

        // mail() gibt immer true zurück in Tests, aber das ist okay
        $this->assertIsBool($result, 'sendEmailNotification sollte boolean zurückgeben');

        // Prüfe ob Mail-Log erstellt wurde
        $mailLogFiles = glob($this->logsPath . '/mails/mail-*.log');
        $this->assertNotEmpty($mailLogFiles, 'Mail-Log-Datei wurde erstellt');

        $mailLogContent = file_get_contents($mailLogFiles[0]);
        $this->assertStringContainsString('test@example.com', $mailLogContent);
        $this->assertStringContainsString('Test Formular', $mailLogContent);
        $this->assertStringContainsString('test_field: Test Wert', $mailLogContent);
    }

    public function testSubmissionCleanup(): void
    {
        // Verwende Reflection um die private Methode direkt zu testen
        $submissionDir = $this->submissionsPath . '/test_form_cleanup';
        mkdir($submissionDir, 0755, true);

        // Erstelle 105 alte Submissions
        for ($i = 1; $i <= 105; $i++) {
            $file = $submissionDir . '/sub_' . str_pad((string)$i, 3, '0', STR_PAD_LEFT) . '.json';
            file_put_contents($file, json_encode(['id' => $i, 'data' => []]));
            // Setze unterschiedliche Timestamps
            touch($file, time() - (106 - $i));
        }

        // Prüfe vor Cleanup
        $this->assertCount(105, glob($submissionDir . '/*.json'));

        // Verwende Reflection um cleanupOldSubmissions zu testen
        $reflection = new \ReflectionClass($this->module);
        $method = $reflection->getMethod('cleanupOldSubmissions');
        $method->setAccessible(true);

        // Cleanup ausführen
        $method->invoke($this->module, $submissionDir, 100);

        // Prüfe ob nur noch 100 Submissions vorhanden sind
        $remainingFiles = glob($submissionDir . '/*.json');
        $this->assertCount(
            100,
            $remainingFiles,
            'Nach Cleanup sollten nur noch 100 Submissions vorhanden sein'
        );

        // Prüfe ob die neuesten Submissions behalten wurden
        $files = array_map('basename', $remainingFiles);
        sort($files);

        // Die ältesten sollten gelöscht sein
        $this->assertNotContains('sub_001.json', $files, 'Älteste Submission sollte gelöscht sein');
        $this->assertNotContains('sub_002.json', $files, 'Zweitälteste Submission sollte gelöscht sein');
        $this->assertNotContains('sub_003.json', $files, 'Drittälteste Submission sollte gelöscht sein');
        $this->assertNotContains('sub_004.json', $files, 'Viertälteste Submission sollte gelöscht sein');
        $this->assertNotContains('sub_005.json', $files, 'Fünftälteste Submission sollte gelöscht sein');
    }

    public function testMailLogCleanup(): void
    {
        $mailLogsPath = $this->logsPath . '/mails';

        // Erstelle 105 alte Log-Dateien
        for ($i = 1; $i <= 105; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $file = $mailLogsPath . '/mail-' . $date . '.log';
            file_put_contents($file, "Test log $i");
            touch($file, strtotime("-$i days"));
        }

        // Verwende Reflection um die private Methode zu testen
        $reflection = new \ReflectionClass($this->module);
        $method = $reflection->getMethod('cleanupOldLogs');
        $method->setAccessible(true);

        // Cleanup ausführen
        $method->invoke($this->module, $mailLogsPath, 100);

        // Prüfe ob nur noch 100 Log-Dateien vorhanden sind
        $remainingFiles = glob($mailLogsPath . '/*.log');
        $this->assertCount(
            100,
            $remainingFiles,
            'Nach Cleanup sollten nur noch 100 Mail-Logs vorhanden sein'
        );
    }

    public function testFieldNameGeneration(): void
    {
        // Test dass Feldnamen automatisch aus Labels generiert werden
        require_once __DIR__ . '/../../../modules/form-builder/FormBlock.php';

        // Verwende Reflection um die private renderField Methode zu testen
        $block = new \FCMS\Modules\FormBuilder\FormBlock();
        $reflection = new \ReflectionClass($block);
        $method = $reflection->getMethod('renderField');
        $method->setAccessible(true);

        // Test-Feld ohne Name
        $field = [
            'type' => 'text',
            'label' => 'Mein Test Feld',
            'name' => '', // Kein Name angegeben
            'placeholder' => '',
            'required' => false,
            'options' => [],
        ];

        $html = $method->invoke($block, $field);

        // Prüfe ob ein name-Attribut generiert wurde
        $this->assertStringContainsString('name="field_mein_test_feld"', $html, 'Feldname sollte aus Label generiert werden');
    }
}
