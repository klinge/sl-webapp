<?php

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\UserController;
use App\Services\UrlGeneratorService;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;
use League\Container\Container;

class UserControllerTest extends TestCase
{
    private $urlGenerator;
    private $request;
    private $logger;
    private $container;
    private $view;
    private $controller;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorService::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->container = $this->createMock(Container::class);
        $this->view = $this->createMock(View::class);

        $this->controller = new UserController(
            $this->urlGenerator,
            $this->request,
            $this->logger,
            $this->container,
            $this->view
        );
    }

    public function testHome()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->view->expects($this->once())
            ->method('render')
            ->with(
                '/user/index',
                $this->callback(function ($data) {
                    return isset($data['title']) && $data['title'] === 'Medlemssidan..';
                })
            )
            ->willReturn($mockResponse);

        $response = $this->controller->home();

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
