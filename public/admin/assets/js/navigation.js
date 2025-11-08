/**
 * Navigation Drag & Drop Manager
 * fCMS Admin Navigation Management
 */

class NavigationManager {
    constructor() {
        this.mainList = document.getElementById('main-nav-list');
        this.footerList = document.getElementById('footer-nav-list');
        this.saveButton = document.getElementById('save-navigation');
        this.hasChanges = false;

        this.init();
    }

    init() {
        if (!this.mainList || !this.footerList) {
            console.error('Navigation lists not found');
            return;
        }

        this.setupDragAndDrop();
        this.setupSaveButton();
    }

    setupDragAndDrop() {
        // Setup for both lists
        [this.mainList, this.footerList].forEach(list => {
            this.makeListSortable(list);
        });
    }

    makeListSortable(list) {
        // Get all navigation page items
        const items = list.querySelectorAll('.navigation-page-item');

        items.forEach(item => {
            item.setAttribute('draggable', 'true');

            // Drag start
            item.addEventListener('dragstart', (e) => {
                item.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', item.innerHTML);
            });

            // Drag end
            item.addEventListener('dragend', (e) => {
                item.classList.remove('dragging');

                // Remove all drag-over classes
                document.querySelectorAll('.navigation-page-item.drag-over').forEach(el => {
                    el.classList.remove('drag-over');
                });
                document.querySelectorAll('.navigation-list.drag-over').forEach(el => {
                    el.classList.remove('drag-over');
                });
            });
        });

        // List drag over
        list.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            const dragging = document.querySelector('.dragging');
            if (!dragging) return;

            const afterElement = this.getDragAfterElement(list, e.clientY);

            if (afterElement == null) {
                list.appendChild(dragging);
            } else {
                list.insertBefore(dragging, afterElement);
            }

            this.markChanged();
        });

        // List drag enter
        list.addEventListener('dragenter', (e) => {
            e.preventDefault();
            list.classList.add('drag-over');
        });

        // List drag leave
        list.addEventListener('dragleave', (e) => {
            if (e.target === list) {
                list.classList.remove('drag-over');
            }
        });

        // List drop
        list.addEventListener('drop', (e) => {
            e.preventDefault();
            list.classList.remove('drag-over');
            this.removeEmptyMessages();
            this.markChanged();
        });
    }

    getDragAfterElement(list, y) {
        const draggableElements = [...list.querySelectorAll('.navigation-page-item:not(.dragging)')];

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

    removeEmptyMessages() {
        document.querySelectorAll('.navigation-list .text-muted').forEach(el => {
            if (el.textContent.includes('Ziehen Sie')) {
                el.remove();
            }
        });
    }

    markChanged() {
        if (!this.hasChanges) {
            this.hasChanges = true;
            this.saveButton.style.display = 'inline-block';
        }
    }

    setupSaveButton() {
        if (!this.saveButton) return;

        this.saveButton.addEventListener('click', async () => {
            await this.saveNavigation();
        });
    }

    async saveNavigation() {
        // Collect slugs from both lists
        const mainSlugs = this.getSlugList(this.mainList);
        const footerSlugs = this.getSlugList(this.footerList);

        const data = {
            main: mainSlugs,
            footer: footerSlugs,
            csrf_token: CSRF_TOKEN
        };

        try {
            this.saveButton.disabled = true;
            this.saveButton.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Speichert...';

            const response = await fetch('/admin/navigation/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Navigation erfolgreich gespeichert', 'success');
                this.hasChanges = false;
                this.saveButton.style.display = 'none';

                // Reload page after short delay to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                this.showNotification('Fehler: ' + (result.error || 'Unbekannter Fehler'), 'danger');
            }
        } catch (error) {
            console.error('Save error:', error);
            this.showNotification('Fehler beim Speichern: ' + error.message, 'danger');
        } finally {
            this.saveButton.disabled = false;
            this.saveButton.innerHTML = '<i class="bi bi-save me-1"></i>Änderungen speichern';
        }
    }

    getSlugList(list) {
        const items = list.querySelectorAll('.navigation-page-item');
        return Array.from(items).map(item => item.dataset.slug).filter(slug => slug);
    }

    showNotification(message, type = 'info') {
        // Create Bootstrap alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.style.position = 'fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '9999';
        alert.style.minWidth = '300px';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alert);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new NavigationManager();
});
