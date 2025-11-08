<?php

declare(strict_types=1);

namespace FCMS\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration-Test für E-Mail-Benachrichtigungen des FormBuilder
 *
 * Testet die E-Mail-Funktionalität durch Prüfung der Mail-Logs
 */
class FormBuilderEmailTest extends TestCase
{
    private string $mailLogPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mailLogPath = __DIR__ . '/../../logs/mails';

        // Stelle sicher dass das Verzeichnis existiert
        if (!is_dir($this->mailLogPath)) {
            mkdir($this->mailLogPath, 0755, true);
        }
    }

    public function testEmailNotificationCreatesLog(): void
    {
        // Lösche alte Test-Logs
        $oldLogs = glob($this->mailLogPath . '/mail-*.log');
        foreach ($oldLogs as $log) {
            unlink($log);
        }

        // Erstelle Test-Formular mit E-Mail-Benachrichtigung
        $formsPath = __DIR__ . '/../../content/forms';
        if (!is_dir($formsPath)) {
            mkdir($formsPath, 0755, true);
        }

        $formId = 'test_email_' . time();
        $formData = [
            'id' => $formId,
            'title' => 'E-Mail Test Formular',
            'description' => 'Test für E-Mail-Benachrichtigungen',
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Name',
                    'required' => false,
                ],
                [
                    'type' => 'email',
                    'name' => 'email',
                    'label' => 'E-Mail',
                    'required' => false,
                ],
            ],
            'settings' => [
                'email_notification' => true,
                'notification_email' => 'test@example.com',
                'success_message' => 'Danke für Ihre Nachricht!',
            ],
            'submit_text' => 'Absenden',
        ];

        file_put_contents(
            $formsPath . '/' . $formId . '.json',
            json_encode($formData, JSON_PRETTY_PRINT)
        );

        // Sende Test-Formular via cURL
        $ch = curl_init('http://localhost:8000/form/submit/' . $formId);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Prüfe Response
        $this->assertEquals(200, $httpCode, 'HTTP-Status sollte 200 sein');

        $data = json_decode($response, true);
        $this->assertNotNull($data, 'Response sollte valid JSON sein');
        $this->assertTrue($data['success'] ?? false, 'Submission sollte erfolgreich sein');

        // Prüfe ob Mail-Log erstellt wurde
        $logFiles = glob($this->mailLogPath . '/mail-' . date('Y-m-d') . '.log');
        $this->assertNotEmpty($logFiles, 'Mail-Log sollte erstellt worden sein');

        // Prüfe Inhalt des Logs
        $logContent = file_get_contents($logFiles[0]);
        $this->assertStringContainsString('test@example.com', $logContent, 'Log sollte Empfänger enthalten');
        $this->assertStringContainsString('E-Mail Test Formular', $logContent, 'Log sollte Formular-Titel enthalten');
        $this->assertStringContainsString('Test User', $logContent, 'Log sollte eingegebenen Namen enthalten');
        $this->assertStringContainsString('testuser@example.com', $logContent, 'Log sollte eingegebene E-Mail enthalten');

        // Cleanup
        @unlink($formsPath . '/' . $formId . '.json');
    }

    public function testMailLogCleanupKeepsOnly100(): void
    {
        // Erstelle 105 Test-Log-Dateien
        for ($i = 0; $i < 105; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $file = $this->mailLogPath . '/mail-' . $date . '.log';
            file_put_contents($file, "Test log $i\n");
            touch($file, strtotime("-$i days"));
        }

        // Prüfe dass 105 Dateien existieren
        $logsBefore = glob($this->mailLogPath . '/mail-*.log');
        $this->assertGreaterThanOrEqual(105, count($logsBefore), 'Es sollten mindestens 105 Log-Dateien existieren');

        // Sende Formular (triggert Cleanup)
        $formsPath = __DIR__ . '/../../content/forms';
        $formId = 'test_cleanup_' . time();
        $formData = [
            'id' => $formId,
            'title' => 'Cleanup Test',
            'fields' => [],
            'settings' => [
                'email_notification' => true,
                'notification_email' => 'cleanup@example.com',
            ],
        ];

        file_put_contents(
            $formsPath . '/' . $formId . '.json',
            json_encode($formData)
        );

        $ch = curl_init('http://localhost:8000/form/submit/' . $formId);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);

        // Prüfe dass nur noch 100 Dateien existieren
        $logsAfter = glob($this->mailLogPath . '/mail-*.log');
        $this->assertLessThanOrEqual(100, count($logsAfter), 'Es sollten maximal 100 Log-Dateien existieren');

        // Cleanup
        @unlink($formsPath . '/' . $formId . '.json');
    }
}
