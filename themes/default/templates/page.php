<div class="container">
    <?php if (isset($page)): ?>
        <article>
            <header class="mb-4">
                <h1><?= htmlspecialchars($page['title']) ?></h1>
            </header>

            <div class="page-content">
                <?php if (isset($page['sections']) && !empty($page['sections'])): ?>
                    <?php foreach ($page['sections'] as $section): ?>
                        <?= $section['html'] ?? '' ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Diese Seite hat noch keinen Inhalt.</p>
                <?php endif; ?>
            </div>
        </article>
    <?php else: ?>
        <div class="text-center py-5">
            <h1>Willkommen bei fCMS</h1>
            <p class="lead">Ihr dateibasiertes Content-Management-System</p>
        </div>
    <?php endif; ?>
</div>
