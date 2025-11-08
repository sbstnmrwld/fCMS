<?php
namespace FCMS\Modules\FormBuilder;

use FCMS\Blocks\AbstractBlock;
use FCMS\Blocks\BlockInterface;

/**
 * FormBlock - Block zum Einbetten von Formularen in Seiten
 *
 * Dieses Block wird vom Form-Builder Modul registriert und ermöglicht
 * das Einbetten von Formularen in Content-Seiten.
 */
class FormBlock extends AbstractBlock implements BlockInterface
{
    /**
     * Block-Metadaten zurückgeben
     */
    public function getMetadata(): array
    {
        return [
            'name' => 'Formular',
            'type' => 'form',
            'category' => 'interactive',
            'icon' => 'bi-envelope',
            'description' => 'Zeigt ein Formular an',
            'keywords' => ['form', 'formular', 'kontakt', 'eingabe'],
            'supports' => [
                'className' => true,
            ],
        ];
    }

    /**
     * Standard-Attribute für neue Blöcke
     */
    public function getDefaultAttributes(): array
    {
        return [
            'formId' => '',
            'className' => 'form-container',
        ];
    }

    /**
     * Block als HTML rendern (Frontend)
     */
    public function render(array $attributes, string $content = ''): string
    {
        $formId = $attributes['formId'] ?? '';
        $className = $attributes['className'] ?? 'form-container';

        if (empty($formId)) {
            // Im Admin Block-Übersicht: Hilfreiche Info-Box anzeigen
            if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/blocks') !== false) {
                return '<div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Formular-Block:</strong> Wählen Sie im Editor ein Formular aus der Formular-Bibliothek aus,
                    um es auf einer Seite anzuzeigen. Das Formular wird dann mit allen Feldern und Submit-Button gerendert.
                </div>';
            }
            // Im Editor: Warnung anzeigen
            if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
                return '<div class="alert alert-warning">
                    <strong>Formular-Block:</strong> Bitte wählen Sie ein Formular aus.
                </div>';
            }
            return '';
        }

        // Formular-Daten laden
        $formPath = __DIR__ . '/../../content/forms/' . basename($formId) . '.json';

        if (!file_exists($formPath)) {
            return '<div class="alert alert-danger">
                Formular nicht gefunden (ID: ' . htmlspecialchars($formId) . ')
            </div>';
        }

        $formData = json_decode(file_get_contents($formPath), true);

        if (!$formData) {
            return '<div class="alert alert-danger">Fehler beim Laden des Formulars</div>';
        }

        // Formular rendern
        return $this->renderFormStyles() . $this->renderForm($formData, $className);
    }

    /**
     * Rendert die CSS-Styles für Formulare
     */
    private function renderFormStyles(): string
    {
        static $stylesRendered = false;

        if ($stylesRendered) {
            return '';
        }

        $stylesRendered = true;
        $cssPath = '/modules/form-builder/assets/form-styles.css';

        return '<link rel="stylesheet" href="' . htmlspecialchars($cssPath) . '">';
    }

    /**
     * Gibt verfügbare Formulare als JSON zurück
     * Diese Methode wird vom Block-Editor über die API aufgerufen
     */
    public static function getAvailableForms(): array
    {
        // Bestimme den Pfad relativ zum Modul
        $formsDir = realpath(__DIR__ . '/../../content/forms/');
        $forms = [];

        if ($formsDir && is_dir($formsDir)) {
            $files = glob($formsDir . '/*.json');
            foreach ($files as $file) {
                $formData = json_decode(file_get_contents($file), true);
                if ($formData && isset($formData['id'])) {
                    $forms[] = [
                        'id' => $formData['id'],
                        'title' => $formData['title'] ?? 'Unbenannt',
                    ];
                }
            }
        }

        return $forms;
    }

    /**
     * Editor-Interface für Admin-Bereich rendern
     */
    public function renderEditor(array $attributes, string $content = ''): string
    {
        $formId = $attributes['formId'] ?? '';
        $className = $attributes['className'] ?? 'form-container';

        // Verfügbare Formulare laden
        $formsDir = __DIR__ . '/../../content/forms/';
        $forms = [];

        if (is_dir($formsDir)) {
            $files = glob($formsDir . '/*.json');
            foreach ($files as $file) {
                $formData = json_decode(file_get_contents($file), true);
                if ($formData) {
                    $forms[] = [
                        'id' => $formData['id'] ?? basename($file, '.json'),
                        'title' => $formData['title'] ?? 'Unbenannt',
                    ];
                }
            }
        }

        $html = '<div class="block-editor form-block-editor">';
        $html .= '<div class="mb-3">';
        $html .= '<label class="form-label">Formular auswählen</label>';
        $html .= '<select class="form-select" data-attribute="formId">';
        $html .= '<option value="">-- Bitte wählen --</option>';

        foreach ($forms as $form) {
            $selected = ($formId === $form['id']) ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($form['id']) . '" ' . $selected . '>';
            $html .= htmlspecialchars($form['title']) . ' (ID: ' . htmlspecialchars($form['id']) . ')';
            $html .= '</option>';
        }

        $html .= '</select>';
        $html .= '</div>';

        $html .= '<div class="mb-3">';
        $html .= '<label class="form-label">CSS-Klasse</label>';
        $html .= '<input type="text" class="form-control" data-attribute="className" ';
        $html .= 'value="' . htmlspecialchars($className) . '" placeholder="form-container">';
        $html .= '</div>';

        if (!empty($formId)) {
            $html .= '<div class="alert alert-info">';
            $html .= '<small><i class="bi bi-info-circle"></i> Das ausgewählte Formular wird auf der Seite angezeigt.</small>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Formular als HTML rendern
     */
    private function renderForm(array $formData, string $className): string
    {
        $formId = $formData['id'] ?? uniqid('form_');
        $title = $formData['title'] ?? 'Formular';
        $description = $formData['description'] ?? '';
        $fields = $formData['fields'] ?? [];
        $submitText = $formData['submit_text'] ?? 'Absenden';

        $html = '<div class="' . htmlspecialchars($className) . '">';

        // Titel und Beschreibung
        if (!empty($title)) {
            $html .= '<h3 class="form-title">' . htmlspecialchars($title) . '</h3>';
        }
        if (!empty($description)) {
            $html .= '<p class="form-description">' . htmlspecialchars($description) . '</p>';
        }

        // Erfolgs-/Fehlermeldungen
        $html .= '<div id="form-message-' . $formId . '" class="form-messages" style="display:none;"></div>';

        // Formular
        $html .= '<form id="form-' . $formId . '" class="fcms-form" method="POST" action="/form/submit/' . htmlspecialchars($formId) . '">';

        // CSRF-Token (falls vorhanden)
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['csrf_token'])) {
            $html .= '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
        }

        // Felder rendern
        foreach ($fields as $field) {
            $html .= $this->renderField($field);
        }

        // Submit-Button
        $html .= '<div class="form-group mt-4">';
        $html .= '<button type="submit" class="btn btn-primary">' . htmlspecialchars($submitText) . '</button>';
        $html .= '</div>';

        $html .= '</form>';
        $html .= '</div>';

        // JavaScript für AJAX-Submission
        $html .= $this->getFormScript($formId);

        return $html;
    }

    /**
     * Einzelnes Formular-Feld rendern
     */
    private function renderField(array $field): string
    {
        $type = $field['type'] ?? 'text';
        $name = $field['name'] ?? '';
        $label = $field['label'] ?? '';
        $required = $field['required'] ?? false;
        $placeholder = $field['placeholder'] ?? '';
        $options = $field['options'] ?? [];

        // Fallback: Wenn kein Name vorhanden, generiere einen aus dem Label
        if (empty($name)) {
            $name = !empty($label) 
                ? 'field_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($label))
                : 'field_' . uniqid();
        }

        $fieldId = 'field_' . uniqid();
        $requiredAttr = $required ? 'required' : '';
        $requiredLabel = $required ? '<span class="text-danger">*</span>' : '';

        $html = '<div class="form-group mb-3">';

        if (!empty($label)) {
            $html .= '<label for="' . $fieldId . '" class="form-label">'
                   . htmlspecialchars($label) . ' ' . $requiredLabel
                   . '</label>';
        }

        switch ($type) {
            case 'textarea':
                $html .= '<textarea id="' . $fieldId . '" name="' . htmlspecialchars($name) . '"
                         class="form-control" placeholder="' . htmlspecialchars($placeholder) . '"
                         ' . $requiredAttr . ' rows="5"></textarea>';
                break;

            case 'select':
                $html .= '<select id="' . $fieldId . '" name="' . htmlspecialchars($name) . '"
                         class="form-select" ' . $requiredAttr . '>';
                $html .= '<option value="">Bitte wählen...</option>';
                foreach ($options as $option) {
                    $html .= '<option value="' . htmlspecialchars($option) . '">'
                           . htmlspecialchars($option) . '</option>';
                }
                $html .= '</select>';
                break;

            case 'checkbox':
                foreach ($options as $option) {
                    $optionId = $fieldId . '_' . uniqid();
                    $html .= '<div class="form-check">';
                    $html .= '<input type="checkbox" id="' . $optionId . '" name="' . htmlspecialchars($name) . '[]"
                             value="' . htmlspecialchars($option) . '" class="form-check-input">';
                    $html .= '<label for="' . $optionId . '" class="form-check-label">'
                           . htmlspecialchars($option) . '</label>';
                    $html .= '</div>';
                }
                break;

            case 'radio':
                foreach ($options as $option) {
                    $optionId = $fieldId . '_' . uniqid();
                    $html .= '<div class="form-check">';
                    $html .= '<input type="radio" id="' . $optionId . '" name="' . htmlspecialchars($name) . '"
                             value="' . htmlspecialchars($option) . '" class="form-check-input" ' . $requiredAttr . '>';
                    $html .= '<label for="' . $optionId . '" class="form-check-label">'
                           . htmlspecialchars($option) . '</label>';
                    $html .= '</div>';
                }
                break;

            case 'email':
                $html .= '<input type="email" id="' . $fieldId . '" name="' . htmlspecialchars($name) . '"
                         class="form-control" placeholder="' . htmlspecialchars($placeholder) . '"
                         ' . $requiredAttr . '>';
                break;

            case 'text':
            default:
                $html .= '<input type="text" id="' . $fieldId . '" name="' . htmlspecialchars($name) . '"
                         class="form-control" placeholder="' . htmlspecialchars($placeholder) . '"
                         ' . $requiredAttr . '>';
                break;
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * JavaScript für AJAX-Formular-Submission
     */
    private function getFormScript(string $formId): string
    {
        return <<<JS
<script>
(function() {
    const form = document.getElementById('form-{$formId}');
    const messageDiv = document.getElementById('form-message-{$formId}');

    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;

        // Button deaktivieren während der Übertragung
        submitBtn.disabled = true;
        submitBtn.textContent = 'Wird gesendet...';

        // Nachricht ausblenden
        messageDiv.style.display = 'none';

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageDiv.className = 'form-messages alert alert-success';
                messageDiv.textContent = data.message || 'Formular erfolgreich gesendet!';
                form.reset();
            } else {
                messageDiv.className = 'form-messages alert alert-danger';
                messageDiv.textContent = data.message || 'Fehler beim Senden des Formulars.';
            }
            messageDiv.style.display = 'block';
        })
        .catch(error => {
            messageDiv.className = 'form-messages alert alert-danger';
            messageDiv.textContent = 'Netzwerkfehler beim Senden des Formulars.';
            messageDiv.style.display = 'block';
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
})();
</script>
JS;
    }
}
