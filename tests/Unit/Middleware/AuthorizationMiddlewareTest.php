<?php

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Config\RouteConfig;
use App\Utils\Session;
use App\Application;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class AuthorizationMiddlewareTest extends TestCase
{
    private $middleware;
    private $app;
    private $request;
    private $router;
    private $logger;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->router = $this->createMock(\AltoRouter::class);
        $this->logger = $this->createMock(\Monolog\Logger::class);
        // Make the mocked Application return the mocked router
        $this->app->method('getRouter')->willReturn($this->router);

        $this->middleware = new AuthorizationMiddlewareFake($this->request, $this->router, $this->logger);

        $this->request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '127.0.0.1']);

        Session::start();
        Session::set('user_id', '123');
    }
    public function testNoRouteFound(): void
    {
        $this->router->method('match')->willReturn(false);
        $this->middleware->handle();
        $this->assertTrue($this->middleware->exitCalled);
    }

    public function testNonAdminUserCanAccessUserRoute(): void
    {
        Session::remove('is_admin');
        $this->router->method('match')->willReturn(['name' => 'user-home']);
        $this->middleware->handle();
        $this->assertFalse($this->middleware->exitCalled);
    }

    public function testAdminUserCanAccessAdminPage(): void
    {
        Session::set('is_admin', true);
        $this->router->method('match')->willReturn(['name' => 'medlem-list']);

        $this->middleware->handle();

        $this->assertFalse($this->middleware->exitCalled);
    }

    public function testNonAdminUserCannotAccessAdminPage(): void
    {
        Session::remove('is_admin');
        $this->router->method('match')->willReturn(['name' => 'medlem-list']);

        $this->middleware->handle();

        $this->assertTrue($this->middleware->exitCalled);
    }

    public function testNonAdminUserCanAccessPageWithNoLogin(): void
    {
        Session::remove('is_admin');

        $this->router->method('match')->willReturn(['name' => 'show-login']);

        $this->assertFalse($this->middleware->exitCalled);
    }

    public function testAdminUserCanAccessPageWithNoLogin(): void
    {
        Session::set('is_admin', true);
        $this->router->method('match')->willReturn(['name' => 'show-login']);
        $this->middleware->handle();
        $this->assertFalse($this->middleware->exitCalled);
    }

    protected function tearDown(): void
    {
        Session::destroy();
    }
}
