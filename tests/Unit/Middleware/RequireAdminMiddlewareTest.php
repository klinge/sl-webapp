<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Middleware\RequireAdminMiddleware;
use App\Utils\Session;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequireAdminMiddlewareTest extends TestCase
{
    private RequireAdminMiddleware $middleware;
    private $handler;

    protected function setUp(): void
    {
        $this->middleware = new RequireAdminMiddleware();
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        Session::start();
        Session::destroy();
        Session::start();
    }

    protected function tearDown(): void
    {
        Session::destroy();
    }

    public function testRedirectsToLoginWhenNotAuthenticated(): void
    {
        $request = (new ServerRequest())->withAttribute('route_name', 'admin-route');
        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
        $this->assertEquals('admin-route', Session::get('redirect_url'));
    }

    public function testRedirectsToUserWhenAuthenticatedButNotAdmin(): void
    {
        Session::set('user_id', 123);
        Session::set('is_admin', false);

        $request = new ServerRequest();
        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user', $response->getHeaderLine('Location'));
    }

    public function testContinuesToHandlerWhenAuthenticatedAdmin(): void
    {
        Session::set('user_id', 123);
        Session::set('is_admin', true);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $request = new ServerRequest();
        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }
}
