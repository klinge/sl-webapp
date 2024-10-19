<?php

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\CsrfMiddleware;
use App\Application;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class CsrfMiddlewareTest extends TestCase
{
    private $app;
    private $request;
    private $middleware;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);

        // Create the partial mock, passing the required arguments to the constructor
        $this->middleware = $this->getMockBuilder(CsrfMiddlewareFake::class)
            ->setConstructorArgs([$this->app, $this->request])
            ->onlyMethods(['sendJsonResponse', 'isAjaxRequest'])
            ->getMock();

        // Clear session before each test
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testCsrfMiddlewareImplementsMiddlewareInterface()
    {
        $this->assertInstanceOf(\App\Middleware\MiddlewareInterface::class, $this->middleware);
    }

    public function testHandleGeneratesNewTokenIfNotSet()
    {
        $this->middleware->handle();
        $this->assertNotEmpty($_SESSION['csrf_token']);
        $this->assertEquals(64, strlen($_SESSION['csrf_token']));
    }

    public function testHandleSkipsValidationForExcludedPath()
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/webhooks');
        $this->request->method('getUri')->willReturn($uri);

        $logger = $this->createMock(\Monolog\Logger::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Call to a path that excludes csrf protection:'));

        $this->app->method('getLogger')->willReturn($logger);

        $this->middleware->handle();
    }

    public function testHandleValidatesTokenForPostRequest()
    {
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getParsedBody')->willReturn(['csrf_token' => 'valid_token']);
        $_SESSION['csrf_token'] = 'valid_token';

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/some/path');
        $this->request->method('getUri')->willReturn($uri);

        $logger = $this->createMock(\Monolog\Logger::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('In csrf middleware. Token in POST was:'));

        $this->app->method('getLogger')->willReturn($logger);

        $this->middleware->handle();
    }

    public function testHandleFailsValidationForInvalidToken()
    {
        $this->middleware->method('isAjaxRequest')->willReturn(true);
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getServerParams')->willReturn(['REMOTE_ADDR' => 'localhost']);
        $this->request->method('getParsedBody')->willReturn(['csrf_token' => 'invalid_token']);
        $_SESSION['csrf_token'] = 'valid_token';

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/some/path');
        $this->request->method('getUri')->willReturn($uri);

        $logger = $this->createMock(\Monolog\Logger::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('In csrf middleware. Token in POST was:'));

        $this->app->method('getLogger')->willReturn($logger);

        $this->middleware->handle();

        $this->assertTrue($this->middleware->exitCalled);
    }

    public function testHandleFailsValidationForAjaxRequestWithInvalidToken()
    {
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getParsedBody')->willReturn(['csrf_token' => 'invalid_token']);
        $this->request->method('getServerParams')->willReturn(['REMOTE_ADDR' => 'localhost']);
        $_SESSION['csrf_token'] = 'valid_token';

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/');
        $this->request->method('getUri')->willReturn($uri);

        $logger = $this->createMock(\Monolog\Logger::class);
        $this->app->method('getLogger')->willReturn($logger);

        $this->middleware->handle();

        $this->assertTrue($this->middleware->exitCalled);
    }
}
