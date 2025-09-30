<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Application;
use App\Utils\ResponseEmitter;
use App\Middleware\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use AltoRouter;
use Exception;

class ApplicationHandler implements RequestHandlerInterface
{
    public function __construct(
        private Application $app,
        private AltoRouter $router
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $match = $this->router->match();

        if ($match === false) {
            return new HtmlResponse("404 - Ingen mappning för denna url. Och dessutom borde detta aldrig kunna hända!!", 404);
        }

        return $this->dispatch($match, $request);
    }

    private function dispatch(array $match, ServerRequestInterface $request): ResponseInterface
    {
        if (is_string($match['target']) && strpos($match['target'], "#") !== false) {
            list($controller, $action) = explode('#', $match['target']);
            $params = $match['params'];

            $controllerClass = "App\\Controllers\\{$controller}";
            if (!class_exists($controllerClass)) {
                throw new Exception("Controller class {$controllerClass} not found");
            }

            if (method_exists($controllerClass, $action)) {
                $controllerInstance = $this->app->getContainer()->get($controllerClass);
                $response = $controllerInstance->{$action}($params);

                // Ensure we always return a ResponseInterface
                if ($response instanceof ResponseInterface) {
                    return $response;
                } else {
                    // Handle legacy controllers that might not return ResponseInterface
                    return new HtmlResponse($response ?? '', 200);
                }
            } else {
                throw new Exception("Method {$action} not found in {$controllerClass}");
            }
        } elseif (is_callable($match['target'])) {
            $result = call_user_func_array($match['target'], [$request, ...$match['params']]);
            return $result instanceof ResponseInterface ? $result : new HtmlResponse($result ?? '', 200);
        } else {
            throw new Exception('Invalid route target');
        }
    }
}
