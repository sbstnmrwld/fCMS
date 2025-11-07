<div class="container-fluid mt-4">
    <div class="page-header mb-4">
        <h1><i class="bi bi-inbox me-2"></i>Formular-Eingaben</h1>
        <p>Eingaben für: <strong><?= htmlspecialchars($form['title'] ?? 'Unbekanntes Formular') ?></strong></p>
    </div>

    <div class="mb-4">
        <a href="/admin/forms" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Zurück zur Übersicht
        </a>
        <a href="/admin/forms/edit/<?= htmlspecialchars($form['id'] ?? '') ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i>Formular bearbeiten
        </a>
    </div>

    <?php if (empty($submissions)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Noch keine Eingaben für dieses Formular vorhanden.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>
                    <?= count($submissions) ?> Eingabe<?= count($submissions) !== 1 ? 'n' : '' ?>
                </h5>
            </div>
            <div class="card-body">
                <?php foreach ($submissions as $index => $submission): ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h6 class="mb-0">
                                Eingabe #<?= $index + 1 ?>
                            </h6>
                            <small class="text-muted">
                                <?= date('d.m.Y H:i', strtotime($submission['timestamp'] ?? 'now')) ?> Uhr
                            </small>
                        </div>

                        <table class="table table-sm">
                            <tbody>
                                <?php foreach ($submission['data'] as $fieldLabel => $fieldValue): ?>
                                    <tr>
                                        <th style="width: 30%;"><?= htmlspecialchars($fieldLabel) ?></th>
                                        <td>
                                            <?php if (is_array($fieldValue)): ?>
                                                <?= htmlspecialchars(implode(', ', $fieldValue)) ?>
                                            <?php else: ?>
                                                <?= nl2br(htmlspecialchars($fieldValue)) ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if (!empty($submission['ip'])): ?>
                            <small class="text-muted">
                                <i class="bi bi-geo me-1"></i>
                                IP: <?= htmlspecialchars($submission['ip']) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
