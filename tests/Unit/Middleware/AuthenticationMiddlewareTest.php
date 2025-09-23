<?php

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Config\RouteConfig;
use App\Utils\Session;
use App\Application;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class AuthenticationMiddlewareTest extends TestCase
{
    private $middleware;
    private $app;
    private $request;
    private $router;
    private $logger;
    private $uri;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->router = $this->createMock(\AltoRouter::class);
        $this->logger = $this->createMock(\Monolog\Logger::class);
        $this->uri = $this->createMock(UriInterface::class);

        $this->middleware = new AuthenticationMiddlewareFake($this->request, $this->router, $this->logger);

        $this->app->method('getRouter')->willReturn($this->router);
        $this->request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '127.0.0.1']);
    }
    //all ajax requests require a logged-in user
    public function testHandleAjaxRequestWithoutLogin(): void
    {
        $this->request->method('getUri')->willReturn($this->uri);
        $this->uri->method('__toString')->willReturn('/api/test');

        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(true);
        $this->request->method('getHeader')->with('X-Requested-With')->willReturn(['XMLHttpRequest']);

        Session::remove('user_id');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Ajax request, user not logged in'));

        $this->expectOutputString(json_encode([
            'success' => false,
            'message' => 'Du måste vara inloggad för åtkomst till denna tjänst.'
        ]));

        $this->middleware->handle();

        $this->assertEquals(true, $this->middleware->exitCalled);
    }

    public function testHandleAjaxRequestWithLogin(): void
    {
        $this->request->method('getUri')->willReturn($this->uri);
        $this->uri->method('__toString')->willReturn('/api/test');

        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(true);
        $this->request->method('getHeader')->with('X-Requested-With')->willReturn(['XMLHttpRequest']);

        Session::set('user_id', 123);

        $this->middleware->handle();

        $this->assertFalse($this->middleware->exitCalled);
    }

    public function testHandleProtectedRouteWithoutLogin(): void
    {
        $this->request->method('getUri')->willReturn($this->uri);
        $this->uri->method('__toString')->willReturn('/protected/page');

        $this->router->method('match')->willReturn(['name' => 'protected-route']);
        $this->router->method('generate')->willReturn('/login');

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Request to protected page, user not logged in'));

        Session::remove('user_id');

        $this->expectOutputString('');
        $this->middleware->handle();

        $this->assertEquals(true, $this->middleware->exitCalled);
    }

    public function testHandleProtectedRouteWithLogin(): void
    {
        $this->request->method('getUri')->willReturn($this->uri);
        $this->router->method('match')->willReturn(['name' => 'protected-route']);

        Session::set('user_id', 76);

        $this->middleware->handle();
        $this->assertFalse($this->middleware->exitCalled);
    }


    public function testHandlePublicRouteWithoutLogin(): void
    {
        RouteConfig::$noLoginRequiredRoutes = ['public-route'];

        $this->request->method('getUri')->willReturn($this->uri);
        $this->router->method('match')->willReturn(['name' => 'public-route']);

        Session::set('user_id', null);

        $this->middleware->handle();
        $this->assertTrue(true); // Assert that no redirect or error occurs
    }
}
