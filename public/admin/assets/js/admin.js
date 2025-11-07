// fCMS Admin - JavaScript

(function() {
    'use strict';

    // Block-Editor
    class BlockEditor {
        constructor(container) {
            this.container = container;
            this.blocks = [];
            this.init();
        }

        init() {
            this.setupDragAndDrop();
            this.setupBlockPalette();
            this.loadBlocks();
        }

        setupDragAndDrop() {
            const blockList = this.container.querySelector('.block-list');
            if (!blockList) return;

            let draggedItem = null;

            blockList.addEventListener('dragstart', (e) => {
                if (e.target.classList.contains('block-list-item')) {
                    draggedItem = e.target;
                    e.target.classList.add('dragging');
                }
            });

            blockList.addEventListener('dragend', (e) => {
                if (e.target.classList.contains('block-list-item')) {
                    e.target.classList.remove('dragging');
                }
            });

            blockList.addEventListener('dragover', (e) => {
                e.preventDefault();
                const afterElement = this.getDragAfterElement(blockList, e.clientY);
                if (afterElement == null) {
                    blockList.appendChild(draggedItem);
                } else {
                    blockList.insertBefore(draggedItem, afterElement);
                }
            });
        }

        getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.block-list-item:not(.dragging)')];

            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;

                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        setupBlockPalette() {
            const palette = document.querySelector('.block-palette');
            if (!palette) return;

            palette.addEventListener('click', (e) => {
                const item = e.target.closest('.block-palette-item');
                if (item) {
                    const blockType = item.dataset.blockType;
                    this.addBlock(blockType);
                }
            });
        }

        addBlock(blockType) {
            // AJAX-Request zum Laden des Block-Editors
            fetch('/admin/api/blocks/editor', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.FCMS.csrfToken
                },
                body: JSON.stringify({ type: blockType })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const blockList = this.container.querySelector('.block-list');
                    const blockItem = document.createElement('div');
                    blockItem.className = 'block-list-item';
                    blockItem.draggable = true;
                    blockItem.innerHTML = data.html;
                    blockList.appendChild(blockItem);
                }
            })
            .catch(error => console.error('Fehler beim Laden des Blocks:', error));
        }

        loadBlocks() {
            // Lädt existierende Blöcke
            const blockList = this.container.querySelector('.block-list');
            if (!blockList) return;

            blockList.querySelectorAll('.block-list-item').forEach(item => {
                item.draggable = true;
            });
        }

        saveBlocks() {
            const blockList = this.container.querySelector('.block-list');
            if (!blockList) return [];

            const blocks = [];
            blockList.querySelectorAll('.block-list-item').forEach(item => {
                const blockData = this.extractBlockData(item);
                blocks.push(blockData);
            });

            return blocks;
        }

        extractBlockData(item) {
            const type = item.dataset.blockType;
            const attributes = {};
            const contentElement = item.querySelector('[data-block-content]');

            // Extrahiere Attribute
            item.querySelectorAll('[data-block-attr]').forEach(el => {
                const attrName = el.dataset.blockAttr;
                attributes[attrName] = el.value || el.textContent;
            });

            return {
                type: type,
                attributes: attributes,
                content: contentElement ? contentElement.value || contentElement.textContent : ''
            };
        }
    }

    // Media Manager
    class MediaManager {
        constructor() {
            this.selectedMedia = null;
            this.init();
        }

        init() {
            this.setupUpload();
            this.setupMediaSelection();
        }

        setupUpload() {
            const uploadForm = document.querySelector('#mediaUploadForm');
            if (!uploadForm) return;

            uploadForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(uploadForm);

                fetch('/admin/api/media/upload', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': window.FCMS.csrfToken
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Upload fehlgeschlagen: ' + data.message);
                    }
                })
                .catch(error => console.error('Upload-Fehler:', error));
            });
        }

        setupMediaSelection() {
            document.querySelectorAll('.media-item').forEach(item => {
                item.addEventListener('click', () => {
                    this.selectMedia(item);
                });
            });
        }

        selectMedia(item) {
            document.querySelectorAll('.media-item').forEach(el => {
                el.classList.remove('selected');
            });
            item.classList.add('selected');
            this.selectedMedia = item.dataset.mediaUrl;
        }

        getSelectedMedia() {
            return this.selectedMedia;
        }
    }

    // Form-Validierung
    class FormValidator {
        constructor(form) {
            this.form = form;
            this.init();
        }

        init() {
            this.form.addEventListener('submit', (e) => {
                if (!this.validate()) {
                    e.preventDefault();
                }
            });
        }

        validate() {
            let isValid = true;
            const requiredFields = this.form.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    this.showError(field, 'Dieses Feld ist erforderlich');
                    isValid = false;
                } else {
                    this.clearError(field);
                }
            });

            return isValid;
        }

        showError(field, message) {
            field.classList.add('is-invalid');
            let feedback = field.nextElementSibling;
            if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                field.parentNode.insertBefore(feedback, field.nextSibling);
            }
            feedback.textContent = message;
        }

        clearError(field) {
            field.classList.remove('is-invalid');
            const feedback = field.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.remove();
            }
        }
    }

    // Initialisierung
    document.addEventListener('DOMContentLoaded', () => {
        // Block-Editor initialisieren
        const editorContainer = document.querySelector('.page-editor');
        if (editorContainer) {
            window.blockEditor = new BlockEditor(editorContainer);
        }

        // Media Manager initialisieren
        if (document.querySelector('.media-library')) {
            window.mediaManager = new MediaManager();
        }

        // Form-Validierung für alle Formulare
        document.querySelectorAll('form[data-validate]').forEach(form => {
            new FormValidator(form);
        });

        // Auto-Slug-Generierung
        const titleInput = document.querySelector('#pageTitle');
        const slugInput = document.querySelector('#pageSlug');
        if (titleInput && slugInput) {
            titleInput.addEventListener('input', () => {
                if (!slugInput.dataset.userModified) {
                    slugInput.value = generateSlug(titleInput.value);
                }
            });
            slugInput.addEventListener('input', () => {
                slugInput.dataset.userModified = 'true';
            });
        }
    });

    // Hilfsfunktionen
    function generateSlug(text) {
        return text
            .toLowerCase()
            .replace(/ä/g, 'ae')
            .replace(/ö/g, 'oe')
            .replace(/ü/g, 'ue')
            .replace(/ß/g, 'ss')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
    }

    // Bestätigungsdialoge
    document.addEventListener('click', (e) => {
        if (e.target.matches('[data-confirm]')) {
            if (!confirm(e.target.dataset.confirm)) {
                e.preventDefault();
            }
        }
    });

})();
