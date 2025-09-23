<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Application;
use App\Traits\JsonResponder;
use App\Utils\Session;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response;
use Monolog\Logger;
use League\Container\Container;

class BaseController
{
    //Add the JsonResponder trait
    use JsonResponder;

    protected ServerRequestInterface $request;
    protected array $sessionData;
    protected Application $app;
    protected Logger $logger;
    protected Container $container;

    public function __construct(Application $app, ServerRequestInterface $request, Logger $logger)
    {
        $this->app = $app;
        $this->request = $request;
        $this->logger = $logger;
        $this->container = $app->getContainer();
        $this->initializeSessionData();
    }

    protected function initializeSessionData(): void
    {
        $this->sessionData = [
            'isLoggedIn' => Session::isLoggedIn(),
            'userId' => Session::get('user_id'),
            'fornamn' => Session::get('fornamn'),
            'isAdmin' => Session::isAdmin()
        ];
    }

    protected function setCsrfToken(): void
    {
        $token = bin2hex(random_bytes(32));
        Session::set('csrf_token', $token);
    }

    protected function validateCsrfToken(string $token): bool
    {
        return Session::get('csrf_token') && hash_equals(Session::get('csrf_token'), $token);
    }

    protected function createUrl(string $routeName, array $params = []): string
    {
        return $this->app->getRouter()->generate($routeName, $params);
    }

    protected function notFoundResponse(): ResponseInterface
    {
        $response = new Response();
        return $response->withStatus(404);
    }
}
