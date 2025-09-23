<?php

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\BaseMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use AltoRouter;
use Monolog\Logger;

class BaseMiddlewareTest extends TestCase
{
    private $request;
    private $router;
    private $logger;
    private $middleware;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->router = $this->createMock(AltoRouter::class);
        $this->logger = $this->createMock(Logger::class);
        $this->middleware = new BaseMiddleware($this->request, $this->router, $this->logger);
    }

    public function testIsAjaxRequestReturnsTrueForXmlHttpRequest()
    {
        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(true);
        $this->request->method('getHeader')->with('X-Requested-With')->willReturn(['XMLHttpRequest']);

        $result = $this->callProtectedMethod($this->middleware, 'isAjaxRequest');

        $this->assertTrue($result);
    }

    public function testIsAjaxRequestReturnsFalseForNormalRequest()
    {
        $this->request->method('hasHeader')->with('X-Requested-With')->willReturn(false);

        $result = $this->callProtectedMethod($this->middleware, 'isAjaxRequest');

        $this->assertFalse($result);
    }

    private function callProtectedMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
