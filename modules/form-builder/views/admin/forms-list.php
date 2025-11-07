<div class="container-fluid mt-4">
    <div class="page-header">
        <h1><i class="bi bi-envelope me-2"></i>Formulare</h1>
        <p>Erstelle und verwalte dynamische Formulare</p>
    </div>

    <div class="mb-4">
        <a href="/admin/forms/create" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Neues Formular erstellen
        </a>
    </div>

    <?php if (empty($forms)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Noch keine Formulare vorhanden. Erstelle dein erstes Formular!
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Titel</th>
                            <th>Beschreibung</th>
                            <th>Felder</th>
                            <th>Erstellt</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($forms as $form): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($form['title']) ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars(substr($form['description'] ?? '', 0, 100)) ?>
                                    <?= strlen($form['description'] ?? '') > 100 ? '...' : '' ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= count($form['fields'] ?? []) ?> Felder
                                    </span>
                                </td>
                                <td>
                                    <?= date('d.m.Y', strtotime($form['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/forms/edit/<?= htmlspecialchars($form['id']) ?>"
                                           class="btn btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="/admin/forms/submissions/<?= htmlspecialchars($form['id']) ?>"
                                           class="btn btn-outline-secondary">
                                            <i class="bi bi-inbox"></i>
                                        </a>
                                        <form method="POST"
                                              action="/admin/forms/delete/<?= htmlspecialchars($form['id']) ?>"
                                              style="display:inline;"
                                              onsubmit="return confirm('Formular wirklich löschen?');">
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
