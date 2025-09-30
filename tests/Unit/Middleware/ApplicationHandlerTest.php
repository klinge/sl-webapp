<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\ApplicationHandler;
use App\Application;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use League\Route\Router;
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
        $this->router = $this->createMock(Router::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->container = $this->createMock(Container::class);

        $this->app->method('getContainer')->willReturn($this->container);

        $this->handler = new ApplicationHandler($this->app, $this->router);
    }

    public function testHandleReturns404ForNoRouteMatch(): void
    {
        $notFoundResponse = new HtmlResponse('Not Found', 404);
        $this->router->method('dispatch')->willReturn($notFoundResponse);

        $response = $this->handler->handle($this->request);

        $this->assertSame($notFoundResponse, $response);
    }



    public function testHandleDispatchesRequestToRouter(): void
    {
        $expectedResponse = new HtmlResponse('Success');
        $this->router->method('dispatch')->willReturn($expectedResponse);

        $response = $this->handler->handle($this->request);

        $this->assertSame($expectedResponse, $response);
    }




}
