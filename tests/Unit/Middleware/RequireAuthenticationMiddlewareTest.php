<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Middleware\RequireAuthenticationMiddleware;
use App\Utils\Session;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequireAuthenticationMiddlewareTest extends TestCase
{
    private RequireAuthenticationMiddleware $middleware;
    private $handler;

    protected function setUp(): void
    {
        $logger = $this->createMock(\Monolog\Logger::class);
        $this->middleware = new RequireAuthenticationMiddleware($logger);
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
        $request = (new ServerRequest())->withUri(new \Laminas\Diactoros\Uri('/medlem'));
        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
        $this->assertEquals('/medlem', Session::get('redirect_url'));
    }

    public function testContinuesToHandlerWhenAuthenticated(): void
    {
        Session::set('user_id', 123);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $request = new ServerRequest();
        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testRedirectsToLoginWhenPathIsRoot(): void
    {
        $request = (new ServerRequest())->withUri(new \Laminas\Diactoros\Uri('/'));
        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
        $this->assertNull(Session::get('redirect_url'));
    }
}
