<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\AuthenticationMiddleware;
use App\Middleware\Contracts\RequestHandlerInterface;
use App\Config\RouteConfig;
use App\Utils\Session;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use AltoRouter;
use Monolog\Logger;

class Psr15AuthenticationMiddlewareTest extends TestCase
{
    private AuthenticationMiddleware $middleware;
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

        $this->middleware = new AuthenticationMiddleware($this->router, $this->logger);

        $this->request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '127.0.0.1']);
        $this->request->method('getUri')->willReturn($this->uri);
        $this->uri->method('__toString')->willReturn('/test/path');
    }

    public function testProcessAjaxRequestWithoutLoginReturnsJsonError(): void
    {
        // Setup AJAX request
        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(true);
        $this->request->method('getHeader')->with('X-Requested-With')->willReturn(['XMLHttpRequest']);

        Session::remove('user_id');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Ajax request, user not logged in'));

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testProcessAjaxRequestWithLoginCallsNextHandler(): void
    {
        // Setup AJAX request with logged in user
        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(true);
        $this->request->method('getHeader')->with('X-Requested-With')->willReturn(['XMLHttpRequest']);

        Session::set('user_id', 123);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessProtectedRouteWithoutLoginReturnsRedirect(): void
    {
        // Setup non-AJAX request to protected route
        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(false);

        $this->router->method('match')->willReturn(['name' => 'protected-route']);
        $this->router->method('generate')->with('show-login')->willReturn('/login');

        Session::remove('user_id');

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Request to protected page, user not logged in'));

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testProcessProtectedRouteWithLoginCallsNextHandler(): void
    {
        // Setup request to protected route with logged in user
        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(false);

        $this->router->method('match')->willReturn(['name' => 'protected-route']);

        Session::set('user_id', 76);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessPublicRouteWithoutLoginCallsNextHandler(): void
    {
        RouteConfig::$noLoginRequiredRoutes = ['public-route'];

        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(false);
        $this->router->method('match')->willReturn(['name' => 'public-route']);

        Session::remove('user_id');

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }
}
