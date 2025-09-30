<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Monolog\Logger;
use App\Traits\JsonResponder;
use App\Middleware\Contracts\MiddlewareInterface;
use League\Route\Router;

abstract class BaseMiddleware implements MiddlewareInterface
{
    use JsonResponder;

    protected Router $router;
    protected Logger $logger;

    public function __construct(Router $router, Logger $logger)
    {
        $this->router = $router;
        $this->logger = $logger;
    }

    protected function isAjaxRequest(ServerRequestInterface $request): bool
    {
        return $request->hasHeader('X-Requested-With') &&
               strtolower($request->getHeader('X-Requested-With')[0]) === 'xmlhttprequest';
    }
}
