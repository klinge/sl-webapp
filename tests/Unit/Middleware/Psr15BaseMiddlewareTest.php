<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\BaseMiddleware;
use App\Middleware\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use AltoRouter;
use Monolog\Logger;

class Psr15BaseMiddlewareTest extends TestCase
{
    private $request;
    private $router;
    private $logger;
    private $middleware;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->router = $this->createMock(AltoRouter::class);
        $this->logger = $this->createMock(Logger::class);

        // Create a concrete implementation for testing
        $this->middleware = new class ($this->router, $this->logger) extends BaseMiddleware {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }

            // Expose protected method for testing
            public function testIsAjaxRequest(ServerRequestInterface $request): bool
            {
                return $this->isAjaxRequest($request);
            }
        };
    }

    public function testIsAjaxRequestReturnsTrueForXmlHttpRequest(): void
    {
        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(true);
        $this->request->method('getHeader')->with('X-Requested-With')->willReturn(['XMLHttpRequest']);

        $result = $this->middleware->testIsAjaxRequest($this->request);

        $this->assertTrue($result);
    }

    public function testIsAjaxRequestReturnsFalseForNormalRequest(): void
    {
        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(false);

        $result = $this->middleware->testIsAjaxRequest($this->request);

        $this->assertFalse($result);
    }

    public function testIsAjaxRequestReturnsFalseForDifferentHeader(): void
    {
        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(true);
        $this->request->method('getHeader')->with('X-Requested-With')->willReturn(['SomeOtherValue']);

        $result = $this->middleware->testIsAjaxRequest($this->request);

        $this->assertFalse($result);
    }
}
