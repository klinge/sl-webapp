<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Application;
use App\Utils\ResponseEmitter;
use App\Middleware\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use League\Route\Router;
use League\Route\Http\Exception\NotFoundException;
use Exception;

class ApplicationHandler implements RequestHandlerInterface
{
    public function __construct(
        private Application $app,
        private Router $router
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->router->dispatch($request);
        } catch (NotFoundException $e) {
            return new HtmlResponse("404 - Ingen mappning för denna url. Och dessutom borde detta aldrig kunna hända!!", 404);
        }
    }
}

