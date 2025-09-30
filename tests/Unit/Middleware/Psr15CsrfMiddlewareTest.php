<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\CsrfMiddleware;
use App\Middleware\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use AltoRouter;
use Monolog\Logger;

class Psr15CsrfMiddlewareTest extends TestCase
{
    private CsrfMiddleware $middleware;
    private $request;
    private $router;
    private $logger;
    private $handler;
    private $uri;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->router = $this->createMock(AltoRouter::class);
        $this->logger = $this->createMock(Logger::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->uri = $this->createMock(UriInterface::class);

        $this->middleware = new CsrfMiddleware($this->router, $this->logger);
        
        $this->request->method('getUri')->willReturn($this->uri);
        $this->request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '127.0.0.1']);
        
        // Clear session before each test
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testProcessGeneratesNewTokenIfNotSet(): void
    {
        $this->uri->method('getPath')->willReturn('/test');
        $this->request->method('getMethod')->willReturn('GET');

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertNotEmpty($_SESSION['csrf_token']);
        $this->assertEquals(64, strlen($_SESSION['csrf_token']));
        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessSkipsValidationForExcludedPath(): void
    {
        $this->uri->method('getPath')->willReturn('/webhooks/test');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Call to a path that excludes csrf protection:'));

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessValidatesTokenForValidPostRequest(): void
    {
        $this->uri->method('getPath')->willReturn('/test');
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getHeaderLine')->with('Content-Type')->willReturn('application/x-www-form-urlencoded');
        $this->request->method('getParsedBody')->willReturn(['csrf_token' => 'valid_token']);
        
        $_SESSION['csrf_token'] = 'valid_token';

        $this->logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('In csrf middleware. Token in POST was:'));

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessFailsValidationForInvalidTokenAjaxRequest(): void
    {
        $this->uri->method('getPath')->willReturn('/test');
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getHeaderLine')->with('Content-Type')->willReturn('application/x-www-form-urlencoded');
        $this->request->method('getParsedBody')->willReturn(['csrf_token' => 'invalid_token']);
        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(true);
        $this->request->method('getHeader')->with('X-Requested-With')->willReturn(['XMLHttpRequest']);
        
        $_SESSION['csrf_token'] = 'valid_token';

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('CSRF token mismatch'));

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testProcessFailsValidationForInvalidTokenNonAjaxRequest(): void
    {
        $this->uri->method('getPath')->willReturn('/test');
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getHeaderLine')->with('Content-Type')->willReturn('application/x-www-form-urlencoded');
        $this->request->method('getParsedBody')->willReturn(['csrf_token' => 'invalid_token']);
        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(false);
        
        $_SESSION['csrf_token'] = 'valid_token';
        
        $this->router->method('generate')->with('tech-error')->willReturn('/error');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('CSRF token mismatch'));

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testProcessHandlesJsonRequest(): void
    {
        $this->uri->method('getPath')->willReturn('/test');
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        
        $body = $this->createMock(StreamInterface::class);
        $body->method('getContents')->willReturn('{"csrf_token": "valid_token"}');
        $this->request->method('getBody')->willReturn($body);
        
        $_SESSION['csrf_token'] = 'valid_token';

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }
}