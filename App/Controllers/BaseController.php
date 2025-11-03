<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UrlGeneratorService;
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
    /** @var array<string, mixed> */
    protected array $sessionData;
    protected UrlGeneratorService $urlGenerator;
    protected Logger $logger;
    protected Container $container;

    public function __construct(UrlGeneratorService $urlGenerator, ServerRequestInterface $request, Logger $logger, Container $container)
    {
        $this->urlGenerator = $urlGenerator;
        $this->request = $request;
        $this->logger = $logger;
        $this->container = $container;
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

    /**
     * Creates a URL for the given route name with optional parameters.
     *
     * @param string $routeName The name of the route
     * @param array<string, mixed> $params Optional route parameters (e.g., ['id' => 123])
     * @return string The generated URL path
     */
    protected function createUrl(string $routeName, array $params = []): string
    {
        return $this->urlGenerator->createUrl($routeName, $params);
    }

    protected function notFoundResponse(): ResponseInterface
    {
        $response = new Response();
        return $response->withStatus(404);
    }
}
