<?php

declare(strict_types=1);

namespace FCMS\Controllers;

use FCMS\Core\SettingsManager;
use FCMS\Core\AdminAssetManager;
use FCMS\Core\LanguageManager;
use FCMS\Core\AuthManager;
use FCMS\Core\CsrfManager;
use FCMS\Exceptions\ValidationException;
use FCMS\Exceptions\StorageException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class SettingsController
{
    private SettingsManager $settingsManager;
    private AdminAssetManager $adminAssets;
    private LanguageManager $lang;
    private AuthManager $auth;
    private CsrfManager $csrf;
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->settingsManager = $container->get(SettingsManager::class);
        $this->adminAssets = $container->get(AdminAssetManager::class);
        $this->lang = $container->get(LanguageManager::class);
        $this->auth = $container->get(AuthManager::class);
        $this->csrf = $container->get(CsrfManager::class);
    }

    /**
     * Zeigt Einstellungs-Formular
     */
    public function index(Request $request, Response $response): Response
    {
        $settings = $this->settingsManager->all();

        $settingsContent = '
    <div class="container-fluid mt-4">
        <h2><i class="bi bi-gear me-2"></i>System-Einstellungen</h2>

        <form method="POST" action="/admin/settings/update" class="mt-4">
            ' . $this->csrf->getTokenField() . '

            <div class="row">
                <div class="col-lg-8">
                    <!-- Website-Grundeinstellungen -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-globe me-2"></i>Website-Grundeinstellungen</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="site_name" class="form-label">
                                    <i class="bi bi-tag me-1"></i>Website-Name
                                </label>
                                <input type="text" class="form-control" id="site_name" name="site_name"
                                       value="' . htmlspecialchars($settings['site']['name'] ?? '') . '" required>
                            </div>

                            <div class="mb-3">
                                <label for="site_tagline" class="form-label">
                                    <i class="bi bi-chat-quote me-1"></i>Slogan / Tagline
                                </label>
                                <input type="text" class="form-control" id="site_tagline" name="site_tagline"
                                       value="' . htmlspecialchars($settings['site']['tagline'] ?? '') . '"
                                       placeholder="Ein kurzer Slogan für Ihre Website">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="site_language" class="form-label">
                                        <i class="bi bi-translate me-1"></i>Sprache
                                    </label>
                                    <select class="form-select" id="site_language" name="site_language">
                                        <option value="de"' . (($settings['site']['language'] ?? 'de') === 'de' ? ' selected' : '') . '>Deutsch</option>
                                        <option value="en"' . (($settings['site']['language'] ?? 'de') === 'en' ? ' selected' : '') . '>English</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="site_timezone" class="form-label">
                                        <i class="bi bi-clock me-1"></i>Zeitzone
                                    </label>
                                    <select class="form-select" id="site_timezone" name="site_timezone">
                                        <option value="Europe/Berlin"' . (($settings['site']['timezone'] ?? 'Europe/Berlin') === 'Europe/Berlin' ? ' selected' : '') . '>Europe/Berlin</option>
                                        <option value="Europe/London"' . (($settings['site']['timezone'] ?? 'Europe/Berlin') === 'Europe/London' ? ' selected' : '') . '>Europe/London</option>
                                        <option value="America/New_York"' . (($settings['site']['timezone'] ?? 'Europe/Berlin') === 'America/New_York' ? ' selected' : '') . '>America/New_York</option>
                                        <option value="UTC"' . (($settings['site']['timezone'] ?? 'Europe/Berlin') === 'UTC' ? ' selected' : '') . '>UTC</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEO-Einstellungen -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-search me-2"></i>SEO-Einstellungen</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="seo_description" class="form-label">
                                    <i class="bi bi-text-paragraph me-1"></i>Meta Description
                                </label>
                                <textarea class="form-control" id="seo_description" name="seo_description" rows="3"
                                          placeholder="Kurze Beschreibung Ihrer Website für Suchmaschinen">'
                                          . htmlspecialchars($settings['seo']['meta_description'] ?? '') . '</textarea>
                                <small class="form-text text-muted">Empfohlene Länge: 150-160 Zeichen</small>
                            </div>

                            <div class="mb-3">
                                <label for="seo_keywords" class="form-label">
                                    <i class="bi bi-tags me-1"></i>Meta Keywords
                                </label>
                                <input type="text" class="form-control" id="seo_keywords" name="seo_keywords"
                                       value="' . htmlspecialchars($settings['seo']['meta_keywords'] ?? '') . '"
                                       placeholder="keyword1, keyword2, keyword3">
                            </div>

                            <div class="mb-3">
                                <label for="seo_robots" class="form-label">
                                    <i class="bi bi-robot me-1"></i>Robots Meta Tag
                                </label>
                                <select class="form-select" id="seo_robots" name="seo_robots">
                                    <option value="index, follow"' . (($settings['seo']['robots'] ?? 'index, follow') === 'index, follow' ? ' selected' : '') . '>Index, Follow (Standard)</option>
                                    <option value="noindex, nofollow"' . (($settings['seo']['robots'] ?? 'index, follow') === 'noindex, nofollow' ? ' selected' : '') . '>NoIndex, NoFollow</option>
                                    <option value="index, nofollow"' . (($settings['seo']['robots'] ?? 'index, follow') === 'index, nofollow' ? ' selected' : '') . '>Index, NoFollow</option>
                                    <option value="noindex, follow"' . (($settings['seo']['robots'] ?? 'index, follow') === 'noindex, follow' ? ' selected' : '') . '>NoIndex, Follow</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Theme-Einstellungen -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-palette me-2"></i>Theme</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="theme_active" class="form-label">Aktives Theme</label>
                                <select class="form-select" id="theme_active" name="theme_active">
                                    <option value="default"' . (($settings['theme']['active'] ?? 'default') === 'default' ? ' selected' : '') . '>Default</option>
                                </select>
                                <small class="form-text text-muted">Weitere Themes können im Themes-Verzeichnis hinzugefügt werden</small>
                            </div>
                        </div>
                    </div>

                    <!-- Wartungsmodus -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-tools me-2"></i>Wartungsmodus</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="maintenance_enabled"
                                       name="maintenance_enabled" value="1"' .
                                       (($settings['maintenance']['enabled'] ?? false) ? ' checked' : '') . '>
                                <label class="form-check-label" for="maintenance_enabled">
                                    Wartungsmodus aktivieren
                                </label>
                            </div>

                            <div class="mb-3">
                                <label for="maintenance_message" class="form-label">Wartungsnachricht</label>
                                <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3">'
                                          . htmlspecialchars($settings['maintenance']['message'] ?? 'Die Website befindet sich derzeit im Wartungsmodus.') . '</textarea>
                            </div>

                            <div class="alert alert-warning mb-0">
                                <small>
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Im Wartungsmodus ist die Website für Besucher nicht erreichbar.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Letzte Aktualisierung -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="text-muted mb-0">
                                <small>
                                    <i class="bi bi-clock-history me-1"></i>
                                    Zuletzt aktualisiert: ' . htmlspecialchars($settings['updated_at'] ?? 'Noch nie') . '
                                </small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Einstellungen speichern
                </button>
                <a href="/admin" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-1"></i>Abbrechen
                </a>
            </div>
        </form>
    </div>';

        $html = renderAdminTemplate('layout', [
            'content' => $settingsContent,
            'title' => 'Einstellungen',
            'activeMenu' => 'settings',
            'adminAssets' => $this->adminAssets,
            'lang' => $this->lang,
            'username' => $this->auth->getUsername(),
            'csrfToken' => $this->csrf->getToken(),
        ], $this->container);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Speichert Einstellungen
     */
    public function update(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // CSRF-Validierung
        if (!$this->csrf->validateToken($data['csrf_token'] ?? '')) {
            $response->getBody()->write('CSRF-Token ungültig');
            return $response->withStatus(403);
        }

        // Einstellungen aktualisieren
        $this->settingsManager->set('site.name', $data['site_name'] ?? 'Meine Website');
        $this->settingsManager->set('site.tagline', $data['site_tagline'] ?? '');
        $this->settingsManager->set('site.language', $data['site_language'] ?? 'de');
        $this->settingsManager->set('site.timezone', $data['site_timezone'] ?? 'Europe/Berlin');

        $this->settingsManager->set('seo.meta_description', $data['seo_description'] ?? '');
        $this->settingsManager->set('seo.meta_keywords', $data['seo_keywords'] ?? '');
        $this->settingsManager->set('seo.robots', $data['seo_robots'] ?? 'index, follow');

        $this->settingsManager->set('theme.active', $data['theme_active'] ?? 'default');

        $this->settingsManager->set('maintenance.enabled', isset($data['maintenance_enabled']));
        $this->settingsManager->set('maintenance.message', $data['maintenance_message'] ?? 'Die Website befindet sich derzeit im Wartungsmodus.');

        return $response->withHeader('Location', '/admin/settings')->withStatus(302);
    }
}
