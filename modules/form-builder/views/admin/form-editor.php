<div class="container-fluid mt-4">
    <div class="page-header mb-4">
        <h1>
            <i class="bi bi-<?= $formId ? 'pencil' : 'plus-circle' ?> me-2"></i>
            <?= $formId ? 'Formular bearbeiten' : 'Neues Formular' ?>
        </h1>
    </div>

    <form method="POST" action="/admin/forms/save" id="formBuilderForm">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
        <input type="hidden" name="form_id" value="<?= htmlspecialchars($formId ?? '') ?>">
        <input type="hidden" name="created_at" value="<?= htmlspecialchars($form['created_at'] ?? date('Y-m-d H:i:s')) ?>">
        <input type="hidden" name="fields" id="fieldsData" value="">

        <div class="row">
            <div class="col-lg-8">
                <!-- Grundeinstellungen -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Grundeinstellungen</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Formular-Titel *</label>
                            <input type="text"
                                   class="form-control"
                                   id="title"
                                   name="title"
                                   value="<?= htmlspecialchars($form['title'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Beschreibung</label>
                            <textarea class="form-control"
                                      id="description"
                                      name="description"
                                      rows="3"><?= htmlspecialchars($form['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Formular-Felder -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Formular-Felder</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="addFieldBtn">
                            <i class="bi bi-plus me-1"></i>Feld hinzufügen
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="formFields">
                            <!-- Felder werden hier dynamisch eingefügt -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Submit-Einstellungen -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Absenden-Einstellungen</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="submit_button_text" class="form-label">Button-Text</label>
                            <input type="text"
                                   class="form-control"
                                   id="submit_button_text"
                                   name="submit_button_text"
                                   value="<?= htmlspecialchars($form['settings']['submit_button_text'] ?? 'Absenden') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="success_message" class="form-label">Erfolgs-Nachricht</label>
                            <textarea class="form-control"
                                      id="success_message"
                                      name="success_message"
                                      rows="3"><?= htmlspecialchars($form['settings']['success_message'] ?? 'Vielen Dank! Ihre Nachricht wurde gesendet.') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- E-Mail-Benachrichtigung -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-envelope me-2"></i>E-Mail-Benachrichtigung</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="email_notification"
                                   name="email_notification"
                                   <?= ($form['settings']['email_notification'] ?? false) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="email_notification">
                                E-Mail bei Eingabe senden
                            </label>
                        </div>

                        <div class="mb-3">
                            <label for="notification_email" class="form-label">Empfänger-E-Mail</label>
                            <input type="email"
                                   class="form-control"
                                   id="notification_email"
                                   name="notification_email"
                                   value="<?= htmlspecialchars($form['settings']['notification_email'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Aktionen -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Formular speichern
                    </button>
                    <a href="/admin/forms" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Abbrechen
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Field Template -->
<template id="fieldTemplate">
    <div class="field-item border rounded p-3 mb-3">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h6 class="mb-0">Feld <span class="field-number"></span></h6>
            <button type="button" class="btn btn-sm btn-danger remove-field">
                <i class="bi bi-trash"></i>
            </button>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Feldtyp</label>
                <select class="form-control field-type">
                    <option value="text">Textfeld</option>
                    <option value="email">E-Mail</option>
                    <option value="tel">Telefon</option>
                    <option value="textarea">Mehrzeiliger Text</option>
                    <option value="select">Auswahl (Dropdown)</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="radio">Radio Buttons</option>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Label</label>
                <input type="text" class="form-control field-label" placeholder="z.B. Name">
            </div>

            <div class="col-md-12 mb-3">
                <label class="form-label">Platzhalter (optional)</label>
                <input type="text" class="form-control field-placeholder" placeholder="z.B. Max Mustermann">
            </div>

            <div class="col-md-6 mb-3 options-container" style="display: none;">
                <label class="form-label">Optionen (eine pro Zeile)</label>
                <textarea class="form-control field-options" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
            </div>

            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input field-required" type="checkbox">
                    <label class="form-check-label">Pflichtfeld</label>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formFields = document.getElementById('formFields');
    const addFieldBtn = document.getElementById('addFieldBtn');
    const fieldTemplate = document.getElementById('fieldTemplate');
    let fieldCounter = 0;

    // Lade existierende Felder
    <?php if (!empty($form['fields'])): ?>
        const existingFields = <?= json_encode($form['fields']) ?>;
        existingFields.forEach(field => addField(field));
    <?php endif; ?>

    // Neues Feld hinzufügen
    addFieldBtn.addEventListener('click', () => addField());

    function addField(data = null) {
        const clone = fieldTemplate.content.cloneNode(true);
        const fieldItem = clone.querySelector('.field-item');
        fieldCounter++;

        // Setze Feldnummer
        clone.querySelector('.field-number').textContent = fieldCounter;

        // Setze Werte wenn vorhanden
        if (data) {
            clone.querySelector('.field-type').value = data.type || 'text';
            clone.querySelector('.field-label').value = data.label || '';
            clone.querySelector('.field-placeholder').value = data.placeholder || '';
            clone.querySelector('.field-required').checked = data.required || false;

            if (data.options) {
                clone.querySelector('.field-options').value = data.options.join('\n');
                clone.querySelector('.options-container').style.display = 'block';
            }
        }

        formFields.appendChild(clone);

        // Event Listener
        const lastField = formFields.lastElementChild;
        const typeSelect = lastField.querySelector('.field-type');
        const optionsContainer = lastField.querySelector('.options-container');
        const removeBtn = lastField.querySelector('.remove-field');

        // Zeige Options-Feld für select/radio
        typeSelect.addEventListener('change', function() {
            if (['select', 'radio', 'checkbox'].includes(this.value)) {
                optionsContainer.style.display = 'block';
            } else {
                optionsContainer.style.display = 'none';
            }
        });

        // Feld entfernen
        removeBtn.addEventListener('click', function() {
            lastField.remove();
            updateFieldNumbers();
        });
    }

    function updateFieldNumbers() {
        const fields = formFields.querySelectorAll('.field-item');
        fields.forEach((field, index) => {
            field.querySelector('.field-number').textContent = index + 1;
        });
    }

    // Formular absenden
    document.getElementById('formBuilderForm').addEventListener('submit', function(e) {
        const fields = [];
        formFields.querySelectorAll('.field-item').forEach(field => {
            const type = field.querySelector('.field-type').value;
            const options = field.querySelector('.field-options').value
                .split('\n')
                .filter(o => o.trim())
                .map(o => o.trim());

            fields.push({
                type: type,
                label: field.querySelector('.field-label').value,
                placeholder: field.querySelector('.field-placeholder').value,
                required: field.querySelector('.field-required').checked,
                options: ['select', 'radio', 'checkbox'].includes(type) ? options : []
            });
        });

        document.getElementById('fieldsData').value = JSON.stringify(fields);
    });
});
</script>
