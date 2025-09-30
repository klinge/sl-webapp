<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\ApplicationHandler;
use App\Application;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use AltoRouter;
use League\Container\Container;

class ApplicationHandlerTest extends TestCase
{
    private ApplicationHandler $handler;
    private $app;
    private $router;
    private $request;
    private $container;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->router = $this->createMock(AltoRouter::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->container = $this->createMock(Container::class);

        $this->app->method('getContainer')->willReturn($this->container);

        $this->handler = new ApplicationHandler($this->app, $this->router);
    }

    public function testHandleReturns404ForNoRouteMatch(): void
    {
        $this->router->method('match')->willReturn(false);

        $response = $this->handler->handle($this->request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('404', (string) $response->getBody());
    }



    public function testHandleThrowsExceptionForNonExistentController(): void
    {
        $match = [
            'target' => 'NonExistentController#testAction',
            'params' => []
        ];

        $this->router->method('match')->willReturn($match);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Controller class App\\Controllers\\NonExistentController not found');

        $this->handler->handle($this->request);
    }

    public function testHandleCallableTarget(): void
    {
        $callable = function ($request) {
            return new HtmlResponse('Callable Response');
        };

        $match = [
            'target' => $callable,
            'params' => []
        ];

        $this->router->method('match')->willReturn($match);

        $response = $this->handler->handle($this->request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertStringContainsString('Callable Response', (string) $response->getBody());
    }

    public function testHandleThrowsExceptionForInvalidTarget(): void
    {
        $match = [
            'target' => 'invalid_target_format',
            'params' => []
        ];

        $this->router->method('match')->willReturn($match);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid route target');

        $this->handler->handle($this->request);
    }
}
