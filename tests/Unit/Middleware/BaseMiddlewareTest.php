<?php

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\BaseMiddleware;
use App\Application;
use Psr\Http\Message\ServerRequestInterface;

class BaseMiddlewareTest extends TestCase
{
    private $app;
    private $request;
    private $middleware;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->middleware = new BaseMiddleware($this->app, $this->request);
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
