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
use League\Route\Router;
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
        $this->router = $this->createMock(Router::class);
        $this->logger = $this->createMock(Logger::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->uri = $this->createMock(UriInterface::class);

        $this->middleware = new AuthenticationMiddleware($this->router, $this->logger);

        $this->request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '127.0.0.1']);
        $this->request->method('getUri')->willReturn($this->uri);
        $this->uri->method('__toString')->willReturn('/test/path');
        $this->uri->method('getPath')->willReturn('/test/path');
        
        // Reset RouteConfig to default state
        RouteConfig::$noLoginRequiredRoutes = [
            'show-login',
            'show-register',
            'login',
            'logout',
            'register',
            'register-activate',
            'show-request-password',
            'handle-request-password',
            'show-reset-password',
            'reset-password',
            '404',
            'home',
            'git-webhook-listener'
        ];
    }

    protected function tearDown(): void
    {
        // Reset RouteConfig to default state
        RouteConfig::$noLoginRequiredRoutes = [
            'show-login',
            'show-register',
            'login',
            'logout',
            'register',
            'register-activate',
            'show-request-password',
            'handle-request-password',
            'show-reset-password',
            'reset-password',
            '404',
            'home',
            'git-webhook-listener'
        ];
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

        // No need to mock router methods since middleware uses path-based routing

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

        // No need to mock router methods since middleware uses path-based routing

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
        // Ensure session is started and user_id is cleared
        Session::start();
        Session::remove('user_id');
        
        // Ensure show-login is in the no-login-required routes
        RouteConfig::$noLoginRequiredRoutes = ['show-login'];

        // Create fresh mocks for this test to avoid conflicts
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/login');
        $uri->method('__toString')->willReturn('/login');
        
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')->with('X-Requested-With')->willReturn(false);
        $request->method('getUri')->willReturn($uri);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '127.0.0.1']);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }
}
