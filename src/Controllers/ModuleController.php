<?php

declare(strict_types=1);

namespace FCMS\Controllers;

use FCMS\Core\ModuleManager;
use FCMS\Core\AdminAssetManager;
use FCMS\Core\LanguageManager;
use FCMS\Core\AuthManager;
use FCMS\Core\CsrfManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class ModuleController
{
    private ModuleManager $moduleManager;
    private AdminAssetManager $adminAssets;
    private LanguageManager $lang;
    private AuthManager $auth;
    private CsrfManager $csrf;
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->moduleManager = $container->get(ModuleManager::class);
        $this->adminAssets = $container->get(AdminAssetManager::class);
        $this->lang = $container->get(LanguageManager::class);
        $this->auth = $container->get(AuthManager::class);
        $this->csrf = $container->get(CsrfManager::class);
    }

    /**
     * Zeigt alle Module
     */
    public function index(Request $request, Response $response): Response
    {
        // Alle Module abrufen
        $allModules = $this->moduleManager->getAllModuleInfo();
        $activeModules = $this->moduleManager->getActiveModules();

        $modulesContent = '<div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2 class="mb-3">' . $this->lang->t('modules.title') . '</h2>
                <p class="text-muted">' . $this->lang->t('modules.description') . '</p>
            </div>
        </div>';

        if (empty($allModules)) {
            $modulesContent .= '
        <div class="alert alert-info" role="alert">
            <h5 class="alert-heading">' . $this->lang->t('modules.no_modules_found') . '</h5>
            <p class="mb-0">' . $this->lang->t('modules.no_modules_description') . '</p>
        </div>';
        } else {
            $modulesContent .= '<div class="row">';
            foreach ($allModules as $moduleId => $moduleInfo) {
                $isActive = in_array($moduleId, $activeModules);
                $statusBadge = $isActive
                    ? '<span class="badge bg-success">' . $this->lang->t('modules.active') . '</span>'
                    : '<span class="badge bg-secondary">' . $this->lang->t('modules.inactive') . '</span>';

                $actionButton = $isActive
                    ? '<a href="/admin/modules/deactivate/' . htmlspecialchars($moduleId) . '" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'' . $this->lang->t('modules.confirm_deactivate_module') . '\');">' . $this->lang->t('modules.deactivate') . '</a>'
                    : '<a href="/admin/modules/activate/' . htmlspecialchars($moduleId) . '" class="btn btn-sm btn-primary">' . $this->lang->t('modules.activate') . '</a>';

                $modulesContent .= '
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">' . htmlspecialchars($moduleInfo['name']) . '</h5>
                            ' . $statusBadge . '
                        </div>
                        <p class="text-muted small mb-2">Version ' . htmlspecialchars($moduleInfo['version']) . '</p>
                        <p class="card-text">' . htmlspecialchars($moduleInfo['description']) . '</p>

                        <div class="mt-3 mb-2">
                            <small class="text-muted">
                                <strong>' . $this->lang->t('modules.author') . ':</strong> ' . htmlspecialchars($moduleInfo['author']) . '<br>
                                <strong>' . $this->lang->t('modules.requires') . ':</strong> fCMS ' . htmlspecialchars($moduleInfo['requires']['fcms'] ?? 'unknown') . '
                            </small>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        ' . $actionButton . '
                    </div>
                </div>
            </div>';
            }
            $modulesContent .= '</div>'; // End row
        }

        $modulesContent .= '</div>'; // End container-fluid

        $html = renderAdminTemplate('layout', [
            'content' => $modulesContent,
            'title' => $this->lang->t('modules.title'),
            'activeMenu' => 'modules',
            'adminAssets' => $this->adminAssets,
            'lang' => $this->lang,
            'username' => $this->auth->getUsername(),
            'csrfToken' => $this->csrf->getToken(),
        ], $this->container);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Aktiviert ein Modul
     */
    public function activate(Request $request, Response $response, array $args): Response
    {
        $moduleId = $args['moduleId'];

        try {
            $this->moduleManager->activate($moduleId);
            // Erfolg - zurück zur Modul-Übersicht
            return $response
                ->withHeader('Location', '/admin/modules')
                ->withStatus(302);
        } catch (\Exception $e) {
            // Fehler - zurück mit Fehlermeldung (könnte man auch in Session speichern)
            return $response
                ->withHeader('Location', '/admin/modules')
                ->withStatus(302);
        }
    }

    /**
     * Deaktiviert ein Modul
     */
    public function deactivate(Request $request, Response $response, array $args): Response
    {
        $moduleId = $args['moduleId'];

        try {
            $this->moduleManager->deactivate($moduleId);
            // Erfolg - zurück zur Modul-Übersicht
            return $response
                ->withHeader('Location', '/admin/modules')
                ->withStatus(302);
        } catch (\Exception $e) {
            // Fehler - zurück mit Fehlermeldung
            return $response
                ->withHeader('Location', '/admin/modules')
                ->withStatus(302);
        }
    }
}
