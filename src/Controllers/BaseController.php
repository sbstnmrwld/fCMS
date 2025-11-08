<?php

declare(strict_types=1);

namespace FCMS\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * Base Controller
 *
 * Basis-Klasse für alle Admin-Controller mit gemeinsamen Funktionen
 */
abstract class BaseController
{
    protected Twig $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    /**
     * Rendert ein Template
     */
    protected function render(Response $response, string $template, array $data = []): Response
    {
        return $this->view->render($response, $template, $data);
    }

    /**
     * JSON-Response
     */
    protected function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * Redirect-Response
     */
    protected function redirect(Response $response, string $url, int $status = 302): Response
    {
        return $response
            ->withHeader('Location', $url)
            ->withStatus($status);
    }

    /**
     * Flash-Message setzen
     */
    protected function flash(Request $request, string $type, string $message): void
    {
        $session = $request->getAttribute('session');
        if ($session) {
            $flash = $session->get('flash', []);
            $flash[$type] = $message;
            $session->set('flash', $flash);
        }
    }

    /**
     * Holt Request-Parameter
     */
    protected function getParam(Request $request, string $key, $default = null)
    {
        $params = $request->getParsedBody();
        return $params[$key] ?? $default;
    }

    /**
     * Holt alle Request-Parameter
     */
    protected function getParams(Request $request): array
    {
        return $request->getParsedBody() ?? [];
    }
}
