<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Application;
use App\Middleware\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use League\Route\Router;
use League\Route\Http\Exception\NotFoundException;

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
            // Get HomeController from container and call pageNotFound method
            $container = $this->app->getContainer();
            $homeController = $container->get('App\\Controllers\\HomeController');
            return $homeController->pageNotFound();
        }
    }
}
