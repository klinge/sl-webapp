<?php

declare(strict_types=1);

namespace App\Middleware\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MiddlewareStack implements RequestHandlerInterface
{
    private array $middlewares = [];
    private RequestHandlerInterface $fallbackHandler;

    public function __construct(RequestHandlerInterface $fallbackHandler)
    {
        $this->fallbackHandler = $fallbackHandler;
    }

    public function add(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->processMiddleware($request, 0);
    }

    public function processMiddleware(ServerRequestInterface $request, int $index): ResponseInterface
    {
        if (!isset($this->middlewares[$index])) {
            return $this->fallbackHandler->handle($request);
        }

        $middleware = $this->middlewares[$index];
        $nextHandler = new class ($this, $index + 1) implements RequestHandlerInterface {
            public function __construct(
                private MiddlewareStack $stack,
                private int $nextIndex
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->stack->processMiddleware($request, $this->nextIndex);
            }
        };

        return $middleware->process($request, $nextHandler);
    }
}