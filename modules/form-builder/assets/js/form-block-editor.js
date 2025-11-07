/**
 * Form Block Editor Integration
 *
 * Registriert den Editor-Renderer für den Form-Block im Block-Editor
 */

// Initialisiere das globale Renderer-Registry
if (!window.blockEditorRenderers) {
    window.blockEditorRenderers = {};
}

// Registriere den Form-Block Renderer
window.blockEditorRenderers['form'] = function(block, editorInstance) {
    const blockId = block.id;
    const formId = block.data.formId || '';

    // Lade Formulare asynchron
    setTimeout(() => {
        fetch('/admin/api/forms/available')
            .then(response => response.json())
            .then(forms => {
                const selectElement = document.querySelector(`#form-select-${blockId}`);
                if (selectElement && forms && forms.length > 0) {
                    forms.forEach(form => {
                        const option = document.createElement('option');
                        option.value = form.id;
                        option.textContent = form.title;
                        if (form.id === formId) {
                            option.selected = true;
                        }
                        selectElement.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Fehler beim Laden der Formulare:', error);
            });
    }, 0);

    return `
        <div class="mb-3">
            <label class="form-label">Formular auswählen</label>
            <select class="form-select" id="form-select-${blockId}" onchange="blockEditor.updateBlockData('${blockId}', 'formId', this.value)">
                <option value="">-- Formular auswählen --</option>
            </select>
            <small class="form-text text-muted">Wählen Sie ein Formular aus der Formular-Bibliothek aus</small>
        </div>
    `;
};
