<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang->t('login.title') ?> - fCMS</title>

    <!-- Bootstrap CSS (aus Admin-Assets) -->
    <link href="<?= $adminAssets->bootstrapCss() ?>" rel="stylesheet">

    <!-- Bootstrap Icons (lokal) -->
    <link href="<?= $adminAssets->bootstrapIcons() ?>" rel="stylesheet">

    <!-- Admin CSS -->
    <link href="<?= $adminAssets->css('admin.css') ?>" rel="stylesheet">
</head>
<body class="login-page">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="bi bi-shield-lock fs-1 text-primary"></i>
                            </div>
                            <h1 class="h3 mb-3"><strong>fCMS</strong></h1>
                            <p class="text-muted"><?= $lang->t('login.title') ?></p>
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($lockoutTime) && $lockoutTime > 0): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-clock me-2"></i>
                                <?= $lang->t('login.locked', ['minutes' => ceil($lockoutTime / 60)]) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/admin/login">
                            <?= $csrfField ?>

                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person me-1"></i>
                                    <?= $lang->t('login.username') ?>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username"
                                           name="username" required autofocus>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="bi bi-key me-1"></i>
                                    <?= $lang->t('login.password') ?>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control" id="password"
                                           name="password" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100"
                                    <?= (isset($lockoutTime) && $lockoutTime > 0) ? 'disabled' : '' ?>>
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                <?= $lang->t('login.submit') ?>
                            </button>
                        </form>

                        <?php if ($config['privacy']['admin_session_notice'] ?? true): ?>
                            <div class="mt-4">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <?= $lang->t('login.session_notice') ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (aus Admin-Assets) -->
    <script src="<?= $adminAssets->bootstrapJs() ?>"></script>
</body>
</html>
