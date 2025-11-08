<?php

/**
 * Admin Routes Configuration
 *
 * Definiert alle Admin-Routen mit Controller-Mappings
 */

use FCMS\Controllers\AuthController;
use FCMS\Controllers\PageController;
use Slim\App;

return function (App $app, $authMiddleware) {
    // Auth Routes (ohne Middleware)
    $app->get('/login', [AuthController::class, 'showLogin']);
    $app->post('/login', [AuthController::class, 'login']);
    $app->get('/logout', [AuthController::class, 'logout']);

    // Dashboard
    $app->get('', $dashboardHandler)->add($authMiddleware);
    $app->get('/', $dashboardHandler)->add($authMiddleware);

    // Page Routes (mit Auth-Middleware)
    $app->get('/pages', [PageController::class, 'index'])->add($authMiddleware);
    $app->get('/pages/new', [PageController::class, 'create'])->add($authMiddleware);
    $app->post('/pages/create', [PageController::class, 'store'])->add($authMiddleware);
    $app->get('/pages/edit/{slug}', [PageController::class, 'edit'])->add($authMiddleware);
    $app->post('/pages/update/{slug}', [PageController::class, 'update'])->add($authMiddleware);
    $app->get('/pages/delete/{slug}', [PageController::class, 'delete'])->add($authMiddleware);

    // Weitere Routes folgen...
};
