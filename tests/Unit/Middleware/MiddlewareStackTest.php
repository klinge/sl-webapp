<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\Contracts\MiddlewareStack;
use App\Middleware\Contracts\MiddlewareInterface;
use App\Middleware\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response;

class MiddlewareStackTest extends TestCase
{
    private $request;
    private $fallbackHandler;
    private MiddlewareStack $stack;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->fallbackHandler = $this->createMock(RequestHandlerInterface::class);
        $this->stack = new MiddlewareStack($this->fallbackHandler);
    }

    public function testEmptyStackCallsFallbackHandler(): void
    {
        $expectedResponse = new Response();

        $this->fallbackHandler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($expectedResponse);

        $response = $this->stack->handle($this->request);

        $this->assertSame($expectedResponse, $response);
    }

    public function testSingleMiddlewareIsProcessed(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $expectedResponse = new Response();

        $middleware->expects($this->once())
            ->method('process')
            ->with($this->request, $this->isInstanceOf(RequestHandlerInterface::class))
            ->willReturn($expectedResponse);

        $this->stack->add($middleware);
        $response = $this->stack->handle($this->request);

        $this->assertSame($expectedResponse, $response);
    }

    public function testMultipleMiddlewareAreProcessedInOrder(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $finalResponse = new Response();

        // First middleware should be called first
        $middleware1->expects($this->once())
            ->method('process')
            ->with($this->request, $this->isInstanceOf(RequestHandlerInterface::class))
            ->willReturnCallback(function ($request, $handler) {
                return $handler->handle($request);
            });

        // Second middleware should be called second
        $middleware2->expects($this->once())
            ->method('process')
            ->with($this->request, $this->isInstanceOf(RequestHandlerInterface::class))
            ->willReturn($finalResponse);

        $this->stack->add($middleware1);
        $this->stack->add($middleware2);

        $response = $this->stack->handle($this->request);

        $this->assertSame($finalResponse, $response);
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $shortCircuitResponse = new Response();

        // First middleware short-circuits
        $middleware1->expects($this->once())
            ->method('process')
            ->with($this->request, $this->isInstanceOf(RequestHandlerInterface::class))
            ->willReturn($shortCircuitResponse);

        // Second middleware should never be called
        $middleware2->expects($this->never())
            ->method('process');

        // Fallback handler should never be called
        $this->fallbackHandler->expects($this->never())
            ->method('handle');

        $this->stack->add($middleware1);
        $this->stack->add($middleware2);

        $response = $this->stack->handle($this->request);

        $this->assertSame($shortCircuitResponse, $response);
    }
}
