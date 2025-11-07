<?php

namespace FCMS\Modules;

use FCMS\Core\AbstractModule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Form Builder Module
 *
 * Dynamische Formular-Erstellung und -Verwaltung
 */
class FormBuilderModule extends AbstractModule
{
    private string $formsPath;

    public function __construct(string $modulePath = '')
    {
        parent::__construct($modulePath);
        // Versuche, den Pfad aus der globalen Konfiguration zu holen
        $configFile = __DIR__ . '/../../config/config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            if (isset($config['paths']['content'])) {
                $this->formsPath = $config['paths']['content'] . '/forms';
            } else {
                $this->formsPath = __DIR__ . '/../../content/forms';
            }
        } else {
            $this->formsPath = __DIR__ . '/../../content/forms';
        }
    }

    public function boot(object $app, object $container): void
    {
        // Stelle sicher, dass Forms-Verzeichnis existiert
        if (!is_dir($this->formsPath)) {
            mkdir($this->formsPath, 0755, true);
        }

        // Submissions-Verzeichnis erstellen
        $config = $container->get('config');
        $submissionsPath = $config['paths']['content'] . '/submissions';
        if (!is_dir($submissionsPath)) {
            mkdir($submissionsPath, 0755, true);
        }

        // FormBlock registrieren
        $this->registerFormBlock($container);

        // Registriere Routen
        $this->registerRoutes($app, $container);
        
        // Registriere API-Endpunkt für verfügbare Formulare
        $this->registerApiRoutes($app, $container);
    }

    /**
     * Registriert API-Endpunkte für das Modul
     */
    private function registerApiRoutes(object $app, object $container): void
    {
        $self = $this;
        
        // API-Endpunkt für verfügbare Formulare (für Block-Editor)
        $app->get('/api/forms/available', function (Request $request, Response $response) use ($container, $self) {
            require_once $self->getFilePath('FormBlock.php');
            $forms = \FCMS\Modules\FormBuilder\FormBlock::getAvailableForms();
            
            $response->getBody()->write(json_encode($forms));
            return $response->withHeader('Content-Type', 'application/json');
        });
    }

    /**
     * Registriere den FormBlock im BlockRegistry
     */
    private function registerFormBlock(object $container): void
    {
        try {
            $blockRegistry = $container->get(\FCMS\Blocks\BlockRegistry::class);

            // Lade FormBlock
            require_once $this->getFilePath('FormBlock.php');

            $formBlock = new \FCMS\Modules\FormBuilder\FormBlock();
            $blockRegistry->register('form', $formBlock);
        } catch (\Exception $e) {
            // Fehler beim Registrieren ignorieren (Block-Registry existiert möglicherweise nicht)
            error_log('FormBlock konnte nicht registriert werden: ' . $e->getMessage());
        }
    }

    private function registerRoutes(object $app, object $container): void
    {
        // Auth-Middleware
        $authMiddleware = function (Request $request, $handler) use ($container) {
            $auth = $container->get(\FCMS\Core\AuthManager::class);
            if (!$auth->isAuthenticated()) {
                return (new \Slim\Psr7\Response())
                    ->withHeader('Location', '/admin/login')
                    ->withStatus(302);
            }
            return $handler->handle($request);
        };

        // Formulare-Liste
        $self = $this;
        $app->get('/forms', function (Request $request, Response $response) use ($container, $self) {
            return $self->showFormsList($request, $response, $container);
        })->add($authMiddleware);

        // Neues Formular erstellen
        $app->get('/forms/create', function (Request $request, Response $response) use ($container, $self) {
            return $self->showFormEditor($request, $response, $container, null);
        })->add($authMiddleware);

        // Formular bearbeiten
        $app->get('/forms/edit/{id}', function (Request $request, Response $response, array $args) use ($container, $self) {
            return $self->showFormEditor($request, $response, $container, $args['id']);
        })->add($authMiddleware);

        // Formular speichern
        $app->post('/forms/save', function (Request $request, Response $response) use ($container, $self) {
            return $self->saveForm($request, $response, $container);
        })->add($authMiddleware);

        // Formular löschen
        $app->post('/forms/delete/{id}', function (Request $request, Response $response, array $args) use ($container, $self) {
            return $self->deleteForm($request, $response, $container, $args['id']);
        })->add($authMiddleware);

        // Eingaben anzeigen
        $app->get('/forms/submissions/{id}', function (Request $request, Response $response, array $args) use ($container, $self) {
            return $self->showSubmissions($request, $response, $container, $args['id']);
        })->add($authMiddleware);
    }

    /**
     * Öffentliche Route für Formular-Submissions (ohne Auth)
     * Diese wird in der public/index.php registriert
     */
    public function registerPublicRoutes(object $app, object $container): void
    {
        // Formular-Submission Handler
        $app->post('/form/submit/{id}', function (Request $request, Response $response, array $args) use ($container) {
            return $this->handleSubmission($request, $response, $container, $args['id']);
        });
    }

    /**
     * Verarbeitet eine Formular-Einsendung
     */
    private function handleSubmission(Request $request, Response $response, object $container, string $formId): Response
    {
        $response = $response->withHeader('Content-Type', 'application/json');

        try {
            // Formular laden
            $formPath = $this->formsPath . '/' . basename($formId) . '.json';

            if (!file_exists($formPath)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Formular nicht gefunden.',
                ]));
                return $response->withStatus(404);
            }

            $formData = json_decode(file_get_contents($formPath), true);

            // POST-Daten abrufen
            $postData = $request->getParsedBody();

            // CSRF-Validierung (falls aktiv)
            if (isset($postData['csrf_token'])) {
                session_start();
                if (!isset($_SESSION['csrf_token']) || $postData['csrf_token'] !== $_SESSION['csrf_token']) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Sicherheitstoken ungültig.',
                    ]));
                    return $response->withStatus(403);
                }
                unset($postData['csrf_token']);
            }

            // Validierung
            $errors = $this->validateSubmission($postData, $formData['fields'] ?? []);

            if (!empty($errors)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Bitte überprüfen Sie Ihre Eingaben.',
                    'errors' => $errors,
                ]));
                return $response->withStatus(400);
            }

            // Submission speichern
            $submissionId = $this->saveSubmission($formId, $postData, $request);

            // E-Mail versenden (falls konfiguriert)
            if (!empty($formData['email_notification'])) {
                $this->sendEmailNotification($formData, $postData, $submissionId);
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => $formData['success_message'] ?? 'Vielen Dank für Ihre Nachricht!',
            ]));

            return $response;

        } catch (\Exception $e) {
            error_log('Form submission error: ' . $e->getMessage());

            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.',
            ]));

            return $response->withStatus(500);
        }
    }

    /**
     * Validiert Formular-Eingaben
     */
    private function validateSubmission(array $data, array $fields): array
    {
        $errors = [];

        foreach ($fields as $field) {
            $name = $field['name'] ?? '';
            $required = $field['required'] ?? false;
            $type = $field['type'] ?? 'text';
            $value = $data[$name] ?? null;

            // Required-Validierung
            if ($required && (empty($value) || (is_array($value) && count($value) === 0))) {
                $errors[$name] = 'Dieses Feld ist erforderlich.';
                continue;
            }

            // Typ-spezifische Validierung
            if (!empty($value)) {
                switch ($type) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$name] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
                        }
                        break;

                    case 'text':
                    case 'textarea':
                        if (isset($field['minLength']) && strlen($value) < $field['minLength']) {
                            $errors[$name] = "Mindestens {$field['minLength']} Zeichen erforderlich.";
                        }
                        if (isset($field['maxLength']) && strlen($value) > $field['maxLength']) {
                            $errors[$name] = "Maximal {$field['maxLength']} Zeichen erlaubt.";
                        }
                        break;
                }
            }
        }

        return $errors;
    }

    /**
     * Speichert eine Submission
     */
    private function saveSubmission(string $formId, array $data, Request $request): string
    {
        $config = require __DIR__ . '/../../config/config.php';
        $submissionsDir = $config['paths']['content'] . '/submissions/' . basename($formId);

        if (!is_dir($submissionsDir)) {
            mkdir($submissionsDir, 0755, true);
        }

        $submissionId = uniqid('sub_', true);
        $submissionFile = $submissionsDir . '/' . $submissionId . '.json';

        $submission = [
            'id' => $submissionId,
            'form_id' => $formId,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $request->getHeaderLine('User-Agent'),
        ];

        file_put_contents($submissionFile, json_encode($submission, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $submissionId;
    }

    /**
     * Versendet E-Mail-Benachrichtigung
     */
    private function sendEmailNotification(array $formData, array $submissionData, string $submissionId): bool
    {
        $to = $formData['email_notification'] ?? '';

        if (empty($to)) {
            return false;
        }

        $subject = 'Neue Formular-Einsendung: ' . ($formData['title'] ?? 'Formular');

        // E-Mail-Body erstellen
        $body = "Neue Formular-Einsendung\n\n";
        $body .= "Formular: " . ($formData['title'] ?? 'Unbenannt') . "\n";
        $body .= "Submission-ID: {$submissionId}\n";
        $body .= "Zeitpunkt: " . date('d.m.Y H:i:s') . "\n\n";
        $body .= "Eingaben:\n";
        $body .= str_repeat('-', 50) . "\n\n";

        foreach ($submissionData as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $body .= "{$key}: {$value}\n";
        }

        // Headers
        $headers = [
            'From' => 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
            'Reply-To' => $submissionData['email'] ?? $to,
            'X-Mailer' => 'fCMS Form Builder',
            'Content-Type' => 'text/plain; charset=UTF-8',
        ];

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "{$key}: {$value}\r\n";
        }

        return mail($to, $subject, $body, $headerString);
    }

    private function showFormsList(Request $request, Response $response, object $container): Response
    {
        $forms = $this->getAllForms();

        $content = $this->loadView('admin/forms-list', [
            'forms' => $forms,
            'modulePath' => $this->modulePath
        ]);

        $html = $this->renderAdminLayout($content, 'Formulare', 'forms', $container);
        $response->getBody()->write($html);
        return $response;
    }

    private function showFormEditor(Request $request, Response $response, object $container, ?string $id): Response
    {
        $form = null;
        if ($id) {
            $form = $this->getForm($id);
        }

        $csrf = $container->get(\FCMS\Core\CsrfManager::class);

        $content = $this->loadView('admin/form-editor', [
            'form' => $form,
            'formId' => $id,
            'modulePath' => $this->modulePath,
            'csrfToken' => $csrf->getToken()
        ]);

        $title = $id ? 'Formular bearbeiten' : 'Neues Formular';
        $html = $this->renderAdminLayout($content, $title, 'forms', $container);
        $response->getBody()->write($html);
        return $response;
    }

    private function saveForm(Request $request, Response $response, object $container): Response
    {
        // Verzeichnis prüfen und ggf. erstellen
        if (!is_dir($this->formsPath)) {
            if (!mkdir($this->formsPath, 0755, true)) {
                error_log('[FormBuilder] Konnte Verzeichnis nicht erstellen: ' . $this->formsPath);
            }
        }

        $data = $request->getParsedBody();
        $csrf = $container->get(\FCMS\Core\CsrfManager::class);

        if (!$csrf->validateToken($data['csrf_token'] ?? '')) {
            error_log('[FormBuilder] CSRF-Validierung fehlgeschlagen. Token: ' . ($data['csrf_token'] ?? 'null'));
            return $response->withHeader('Location', '/admin/forms')->withStatus(302);
        }

        $formId = !empty($data['form_id']) ? $data['form_id'] : uniqid('form_');

        // E-Mail-Benachrichtigung für Submission-Handler
        $emailNotification = null;
        if (!empty($data['email_notification']) && !empty($data['notification_email'])) {
            $emailNotification = $data['notification_email'];
        }

        $formData = [
            'id' => $formId,
            'title' => $data['title'] ?? 'Unbenanntes Formular',
            'description' => $data['description'] ?? '',
            'fields' => json_decode($data['fields'] ?? '[]', true),
            'submit_text' => $data['submit_button_text'] ?? 'Absenden',
            'success_message' => $data['success_message'] ?? 'Vielen Dank! Ihre Nachricht wurde gesendet.',
            'email_notification' => $emailNotification,
            'settings' => [
                'submit_button_text' => $data['submit_button_text'] ?? 'Absenden',
                'success_message' => $data['success_message'] ?? 'Vielen Dank! Ihre Nachricht wurde gesendet.',
                'email_notification' => !empty($data['email_notification']),
                'notification_email' => $data['notification_email'] ?? '',
            ],
            'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s'),
        ];

        $file = $this->formsPath . '/' . $formId . '.json';
        $result = @file_put_contents($file, json_encode($formData, JSON_PRETTY_PRINT));
        if ($result === false) {
            error_log('[FormBuilder] Fehler beim Schreiben der Datei: ' . $file);
            error_log('[FormBuilder] formsPath: ' . $this->formsPath);
            error_log('[FormBuilder] formId: ' . $formId);
            error_log('[FormBuilder] Daten: ' . json_encode($formData));
        }

        return $response->withHeader('Location', '/admin/forms')->withStatus(302);
    }

    private function getAllForms(): array
    {
        $forms = [];
        $files = glob($this->formsPath . '/*.json');
        foreach ($files as $file) {
            $json = file_get_contents($file);
            $form = json_decode($json, true);
            if ($form) {
                $forms[] = $form;
            }
        }
        return $forms;
    }

    private function getForm(string $id): ?array
    {
        $file = $this->formsPath . '/' . $id . '.json';
        if (!file_exists($file)) {
            return null;
        }
        $json = file_get_contents($file);
        return json_decode($json, true);
    }

    private function deleteForm(Request $request, Response $response, object $container, string $id): Response
    {
        $file = $this->formsPath . '/' . $id . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
        return $response->withHeader('Location', '/admin/forms')->withStatus(302);
    }

    private function showSubmissions(Request $request, Response $response, object $container, string $id): Response
    {
        $form = $this->getForm($id);
        $submissions = $this->getSubmissions($id);
        $content = $this->loadView('admin/submissions', [
            'form' => $form,
            'submissions' => $submissions,
            'modulePath' => $this->modulePath
        ]);
        $html = $this->renderAdminLayout($content, 'Formular-Eingaben', 'forms', $container);
        $response->getBody()->write($html);
        return $response;
    }

    private function getSubmissions(string $formId): array
    {
        $config = require __DIR__ . '/../../config/config.php';
        $submissionsPath = $config['paths']['content'] . '/submissions/' . basename($formId);

        if (!is_dir($submissionsPath)) {
            return [];
        }

        $submissions = [];
        $files = glob($submissionsPath . '/*.json');

        foreach ($files as $file) {
            $json = file_get_contents($file);
            $submission = json_decode($json, true);
            if ($submission) {
                $submissions[] = $submission;
            }
        }

        // Sortiere nach Datum (neueste zuerst)
        usort($submissions, function($a, $b) {
            return strtotime($b['timestamp'] ?? 0) - strtotime($a['timestamp'] ?? 0);
        });

        return $submissions;
    }

    private function renderAdminLayout(string $content, string $title, string $activeMenu, object $container): string
    {
        $adminAssets = $container->get(\FCMS\Core\AdminAssetManager::class);
        $lang = $container->get(\FCMS\Core\LanguageManager::class);
        $auth = $container->get(\FCMS\Core\AuthManager::class);
        $csrf = $container->get(\FCMS\Core\CsrfManager::class);

        // Verwende die globale renderAdminTemplate Funktion
        return renderAdminTemplate('layout', [
            'content' => $content,
            'title' => $title,
            'activeMenu' => $activeMenu,
            'adminAssets' => $adminAssets,
            'lang' => $lang,
            'username' => $auth->getUsername(),
            'csrfToken' => $csrf->getToken(),
        ], $container);
    }

    public function activate(): void
    {
        // Erstelle Forms-Verzeichnis wenn nicht vorhanden
        if (!is_dir($this->formsPath)) {
            mkdir($this->formsPath, 0755, true);
        }
    }

    public function deactivate(): void
    {
        // Nichts zu tun beim Deaktivieren
    }

    /**
     * Registriert Admin-Assets für den Block-Editor
     */
    public function getAdminAssets(): array
    {
        return [
            'css' => [],
            'js' => [
                '/modules/form-builder/assets/js/form-block-editor.js'
            ]
        ];
    }
}
