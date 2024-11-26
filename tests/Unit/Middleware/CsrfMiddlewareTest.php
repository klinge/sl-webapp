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
    private $logger;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->logger = $this->createMock(\Monolog\Logger::class);

        // Create the partial mock, passing the required arguments to the constructor
        $this->middleware = $this->getMockBuilder(CsrfMiddlewareFake::class)
            ->setConstructorArgs([$this->app, $this->request])
            ->onlyMethods(['jsonResponse', 'isAjaxRequest'])
            ->getMock();

        $this->app->method('getLogger')->willReturn($this->logger);
        // Clear session before each test
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    private function setupMockUri(string $path): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn($path);
        $this->request->method('getUri')->willReturn($uri);
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
        $this->setupMockUri('/webhooks');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Call to a path that excludes csrf protection:'));

        $this->middleware->handle();
    }

    public function testHandleValidatesTokenForPostRequest()
    {
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getParsedBody')->willReturn(['csrf_token' => 'valid_token']);
        $_SESSION['csrf_token'] = 'valid_token';

        $this->setupMockUri('/some/path');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('In csrf middleware. Token in POST was:'));

        $this->middleware->handle();
    }

    public function testHandleFailsValidationForInvalidToken()
    {
        $this->middleware->method('isAjaxRequest')->willReturn(true);
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getServerParams')->willReturn(['REMOTE_ADDR' => 'localhost']);
        $this->request->method('getParsedBody')->willReturn(['csrf_token' => 'invalid_token']);
        $_SESSION['csrf_token'] = 'valid_token';

        $this->setupMockUri('/some/path');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('In csrf middleware. Token in POST was:'));


        $this->middleware->handle();

        $this->assertTrue($this->middleware->exitCalled);
    }

    public function testHandleFailsValidationForAjaxRequestWithInvalidToken()
    {
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getParsedBody')->willReturn(['csrf_token' => 'invalid_token']);
        $this->request->method('getServerParams')->willReturn(['REMOTE_ADDR' => 'localhost']);
        $_SESSION['csrf_token'] = 'valid_token';

        $this->setupMockUri('/');

        $this->middleware->handle();

        $this->assertTrue($this->middleware->exitCalled);
    }
}
