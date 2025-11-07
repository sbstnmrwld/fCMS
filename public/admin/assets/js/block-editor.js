/**
 * fCMS Block Editor
 *
 * Dynamischer Block-Editor für Seiten-Inhalte
 */

class BlockEditor {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.blocks = [];
        this.blockTypes = {
            'paragraph': {
                name: 'Absatz',
                icon: '<i class="bi bi-text-paragraph"></i>',
                defaultData: { text: '' }
            },
            'heading': {
                name: 'Überschrift',
                icon: '<i class="bi bi-type-h1"></i>',
                defaultData: { text: '', level: 2 }
            },
            'image': {
                name: 'Bild',
                icon: '<i class="bi bi-image"></i>',
                defaultData: { url: '', alt: '', caption: '' }
            },
            'quote': {
                name: 'Zitat',
                icon: '<i class="bi bi-quote"></i>',
                defaultData: { text: '', author: '' }
            },
            'list': {
                name: 'Liste',
                icon: '<i class="bi bi-list-ul"></i>',
                defaultData: { items: [], ordered: false }
            }
        };

        this.init();
    }

    init() {
        this.renderToolbar();
        this.renderBlocksContainer();
        this.loadBlocks();
    }

    renderToolbar() {
        const toolbar = document.createElement('div');
        toolbar.className = 'block-editor-toolbar mb-3';
        toolbar.innerHTML = `
            <div class="btn-group" role="group">
                ${Object.entries(this.blockTypes).map(([type, config]) => `
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="blockEditor.addBlock('${type}')">
                        ${config.icon} ${config.name}
                    </button>
                `).join('')}
            </div>
        `;
        this.container.appendChild(toolbar);
    }

    renderBlocksContainer() {
        const blocksContainer = document.createElement('div');
        blocksContainer.id = 'blocks-container';
        blocksContainer.className = 'blocks-container';
        this.container.appendChild(blocksContainer);

        // Hidden input für JSON-Daten
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'blocks_json';
        hiddenInput.id = 'blocks_json';
        this.container.appendChild(hiddenInput);
    }

    loadBlocks(blocksData = null) {
        if (!blocksData) {
            // Lade aus hidden input falls vorhanden
            const dataInput = document.getElementById('initial_blocks_data');
            if (dataInput && dataInput.value) {
                try {
                    blocksData = JSON.parse(dataInput.value);
                } catch (e) {
                    console.error('Fehler beim Laden der Blocks:', e);
                    blocksData = [];
                }
            }
        }

        if (blocksData && blocksData.length > 0) {
            blocksData.forEach(block => {
                this.addBlock(block.type, block.data, false);
            });
        }
    }

    addBlock(type, data = null, focus = true) {
        const blockConfig = this.blockTypes[type];
        if (!blockConfig) return;

        const blockId = 'block_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        const blockData = data || blockConfig.defaultData;

        const block = {
            id: blockId,
            type: type,
            data: blockData
        };

        this.blocks.push(block);
        this.renderBlock(block);
        this.updateJSON();

        if (focus) {
            // Fokussiere das erste Input-Feld des neuen Blocks
            setTimeout(() => {
                const blockElement = document.getElementById(blockId);
                const firstInput = blockElement.querySelector('input, textarea');
                if (firstInput) firstInput.focus();
            }, 100);
        }
    }

    renderBlock(block) {
        const container = document.getElementById('blocks-container');
        const blockElement = document.createElement('div');
        blockElement.id = block.id;
        blockElement.className = 'block-item card mb-3';
        blockElement.setAttribute('data-block-type', block.type);

        const blockConfig = this.blockTypes[block.type];

        blockElement.innerHTML = `
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="block-type-label">${blockConfig.icon} ${blockConfig.name}</span>
                <div class="block-actions">
                    <button type="button" class="btn btn-sm btn-link" onclick="blockEditor.moveBlockUp('${block.id}')" title="Nach oben">
                        <i class="bi bi-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-link" onclick="blockEditor.moveBlockDown('${block.id}')" title="Nach unten">
                        <i class="bi bi-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-link text-danger" onclick="blockEditor.deleteBlock('${block.id}')" title="Löschen">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                ${this.renderBlockEditor(block)}
            </div>
        `;

        container.appendChild(blockElement);
    }

    renderBlockEditor(block) {
        switch (block.type) {
            case 'paragraph':
                return `
                    <div class="mb-3">
                        <textarea class="form-control" rows="5" placeholder="Text eingeben..."
                                  onchange="blockEditor.updateBlockData('${block.id}', 'text', this.value)">${block.data.text || ''}</textarea>
                    </div>
                `;

            case 'heading':
                return `
                    <div class="mb-3">
                        <input type="text" class="form-control mb-2" placeholder="Überschrift eingeben..."
                               value="${block.data.text || ''}"
                               onchange="blockEditor.updateBlockData('${block.id}', 'text', this.value)">
                        <select class="form-select" onchange="blockEditor.updateBlockData('${block.id}', 'level', parseInt(this.value))">
                            ${[1,2,3,4,5,6].map(level => `
                                <option value="${level}" ${block.data.level === level ? 'selected' : ''}>H${level}</option>
                            `).join('')}
                        </select>
                    </div>
                `;

            case 'image':
                return `
                    <div class="mb-3">
                        <label class="form-label">Bild-URL</label>
                        <input type="text" class="form-control mb-2" placeholder="https://..."
                               value="${block.data.url || ''}"
                               onchange="blockEditor.updateBlockData('${block.id}', 'url', this.value)">
                        <label class="form-label">Alt-Text</label>
                        <input type="text" class="form-control mb-2" placeholder="Bildbeschreibung"
                               value="${block.data.alt || ''}"
                               onchange="blockEditor.updateBlockData('${block.id}', 'alt', this.value)">
                        <label class="form-label">Bildunterschrift (optional)</label>
                        <input type="text" class="form-control" placeholder="Bildunterschrift"
                               value="${block.data.caption || ''}"
                               onchange="blockEditor.updateBlockData('${block.id}', 'caption', this.value)">
                        ${block.data.url ? `<img src="${block.data.url}" class="img-fluid mt-2" alt="${block.data.alt || ''}">` : ''}
                    </div>
                `;

            case 'quote':
                return `
                    <div class="mb-3">
                        <textarea class="form-control mb-2" rows="3" placeholder="Zitat eingeben..."
                                  onchange="blockEditor.updateBlockData('${block.id}', 'text', this.value)">${block.data.text || ''}</textarea>
                        <input type="text" class="form-control" placeholder="Autor (optional)"
                               value="${block.data.author || ''}"
                               onchange="blockEditor.updateBlockData('${block.id}', 'author', this.value)">
                    </div>
                `;

            case 'list':
                return `
                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="list_ordered_${block.id}"
                                   ${block.data.ordered ? 'checked' : ''}
                                   onchange="blockEditor.updateBlockData('${block.id}', 'ordered', this.checked)">
                            <label class="form-check-label" for="list_ordered_${block.id}">
                                Nummerierte Liste
                            </label>
                        </div>
                        <textarea class="form-control" rows="5" placeholder="Ein Punkt pro Zeile..."
                                  onchange="blockEditor.updateListItems('${block.id}', this.value)">${(block.data.items || []).join('\n')}</textarea>
                        <small class="form-text text-muted">Geben Sie jeden Listenpunkt in eine neue Zeile ein</small>
                    </div>
                `;

            default:
                return '<p>Unbekannter Block-Typ</p>';
        }
    }

    updateBlockData(blockId, key, value) {
        const block = this.blocks.find(b => b.id === blockId);
        if (block) {
            block.data[key] = value;
            this.updateJSON();

            // Bei Bild-URL Änderung: Bild neu rendern
            if (block.type === 'image' && key === 'url') {
                this.reRenderBlock(blockId);
            }
        }
    }

    updateListItems(blockId, text) {
        const block = this.blocks.find(b => b.id === blockId);
        if (block) {
            block.data.items = text.split('\n').filter(item => item.trim() !== '');
            this.updateJSON();
        }
    }

    reRenderBlock(blockId) {
        const block = this.blocks.find(b => b.id === blockId);
        if (block) {
            const blockElement = document.getElementById(blockId);
            const cardBody = blockElement.querySelector('.card-body');
            cardBody.innerHTML = this.renderBlockEditor(block);
        }
    }

    moveBlockUp(blockId) {
        const index = this.blocks.findIndex(b => b.id === blockId);
        if (index > 0) {
            [this.blocks[index], this.blocks[index - 1]] = [this.blocks[index - 1], this.blocks[index]];
            this.reRenderAllBlocks();
        }
    }

    moveBlockDown(blockId) {
        const index = this.blocks.findIndex(b => b.id === blockId);
        if (index < this.blocks.length - 1) {
            [this.blocks[index], this.blocks[index + 1]] = [this.blocks[index + 1], this.blocks[index]];
            this.reRenderAllBlocks();
        }
    }

    deleteBlock(blockId) {
        if (confirm('Block wirklich löschen?')) {
            this.blocks = this.blocks.filter(b => b.id !== blockId);
            document.getElementById(blockId).remove();
            this.updateJSON();
        }
    }

    reRenderAllBlocks() {
        const container = document.getElementById('blocks-container');
        container.innerHTML = '';
        this.blocks.forEach(block => this.renderBlock(block));
        this.updateJSON();
    }

    updateJSON() {
        const jsonData = this.blocks.map(block => ({
            type: block.type,
            data: block.data
        }));

        document.getElementById('blocks_json').value = JSON.stringify(jsonData);
    }

    getBlocks() {
        return this.blocks.map(block => ({
            type: block.type,
            data: block.data
        }));
    }
}

// Global instance
let blockEditor;
