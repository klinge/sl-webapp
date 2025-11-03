<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\HomeController;
use App\Services\UrlGeneratorService;
use App\Utils\View;
use App\Utils\Session;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;
use League\Container\Container;

class HomeControllerTest extends TestCase
{
    private HomeController $controller;
    private $urlGenerator;
    private $request;
    private $logger;
    private $container;
    private $view;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorService::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->container = $this->createMock(Container::class);
        $this->view = $this->createMock(View::class);

        $this->controller = new HomeController(
            $this->urlGenerator,
            $this->request,
            $this->logger,
            $this->container,
            $this->view
        );
    }

    public function testIndexRendersLoginWhenNotLoggedIn(): void
    {
        Session::destroy();
        
        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('login/viewLogin')
            ->willReturn($mockResponse);

        $response = $this->controller->index();

        $this->assertSame($mockResponse, $response);
    }

    public function testIndexRendersHomeWhenLoggedInAsAdmin(): void
    {
        Session::start();
        $_SESSION['user_id'] = 1;
        $_SESSION['is_admin'] = true;
        
        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('home')
            ->willReturn($mockResponse);

        $response = $this->controller->index();

        $this->assertSame($mockResponse, $response);
    }

    public function testIndexRendersUserIndexWhenLoggedInAsRegularUser(): void
    {
        Session::start();
        $_SESSION['user_id'] = 1;
        $_SESSION['is_admin'] = false;
        
        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('user/index')
            ->willReturn($mockResponse);

        $response = $this->controller->index();

        $this->assertSame($mockResponse, $response);
    }

    public function testPageNotFoundRenders404View(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('404')
            ->willReturn($mockResponse);

        $response = $this->controller->pageNotFound();

        $this->assertSame($mockResponse, $response);
    }

    public function testTechnicalErrorRendersErrorView(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('viewTechnicalError')
            ->willReturn($mockResponse);

        $response = $this->controller->technicalError();

        $this->assertSame($mockResponse, $response);
    }

    protected function tearDown(): void
    {
        Session::destroy();
    }
}