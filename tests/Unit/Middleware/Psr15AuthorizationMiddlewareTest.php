<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\AuthorizationMiddleware;
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

class Psr15AuthorizationMiddlewareTest extends TestCase
{
    private AuthorizationMiddleware $middleware;
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

        $this->middleware = new AuthorizationMiddleware($this->router, $this->logger);

        $this->request->method('getUri')->willReturn($this->uri);
        $this->request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '127.0.0.1']);
        $this->request->method('getAttribute')->with('route_name')->willReturn('protected-route');
        $this->uri->method('__toString')->willReturn('/test/path');
        $this->uri->method('getPath')->willReturn('/test/path');

        Session::start();
        Session::set('user_id', '123');
    }

    protected function tearDown(): void
    {
        Session::destroy();
    }

    public function testProcessUnknownRouteWithAdminCallsHandler(): void
    {
        Session::set('is_admin', true);
        $this->uri->method('getPath')->willReturn('/unknown/path');

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessAdminUserCanAccessAdminPage(): void
    {
        Session::set('is_admin', true);
        $this->uri->method('getPath')->willReturn('/admin/page');

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessNonAdminUserCanAccessUserRoute(): void
    {
        Session::remove('is_admin');
        Session::set('user_id', 123); // User must be logged in

        // Create fresh mocks for this test
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/user');
        $uri->method('__toString')->willReturn('/user');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '127.0.0.1']);
        $request->method('getAttribute')->with('route_name')->willReturn('user-home');

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessNonAdminUserCanAccessOpenRoute(): void
    {
        Session::remove('is_admin');
        Session::set('user_id', 123); // User must be logged in
        RouteConfig::$noLoginRequiredRoutes = ['show-login'];

        // Create fresh mocks for this test
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/login');
        $uri->method('__toString')->willReturn('/login');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '127.0.0.1']);
        $request->method('getAttribute')->with('route_name')->willReturn('show-login');

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessNonAdminUserCannotAccessAdminPageAjaxRequest(): void
    {
        Session::remove('is_admin');
        $this->uri->method('getPath')->willReturn('/admin/page');

        // Setup AJAX request
        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(true);
        $this->request->method('getHeader')->with('X-Requested-With')->willReturn(['XMLHttpRequest']);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Request to an admin page, user is not admin'));

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testProcessNonAdminUserCannotAccessAdminPageNonAjaxRequest(): void
    {
        Session::remove('is_admin');
        $this->uri->method('getPath')->willReturn('/admin/page');

        // Setup non-AJAX request
        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(false);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Request to an admin page, user is not admin'));

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
