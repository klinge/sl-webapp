<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Auth;

use PHPUnit\Framework\TestCase;
use App\Controllers\Auth\RegistrationController;
use App\Services\UrlGeneratorService;
use App\Services\Auth\UserAuthenticationService;
use App\Utils\View;
use App\Utils\Session;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;
use League\Container\Container;

class RegistrationControllerTest extends TestCase
{
    private RegistrationController $controller;
    private $urlGenerator;
    private $request;
    private $logger;
    private $container;
    private $userAuthService;
    private $view;
    private string $turnstileSecret = 'test-secret';

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorService::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->container = $this->createMock(Container::class);
        $this->userAuthService = $this->createMock(UserAuthenticationService::class);
        $this->view = $this->createMock(View::class);

        // Mock server params for REMOTE_ADDR
        $this->request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '127.0.0.1']);

        $this->controller = new RegistrationController(
            $this->urlGenerator,
            $this->request,
            $this->logger,
            $this->container,
            $this->turnstileSecret,
            $this->userAuthService,
            $this->view
        );

        Session::start();
    }

    public function testShowRegisterRendersRegistrationView(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        
        $this->view->expects($this->once())
            ->method('render')
            ->with('login/viewRegisterAccount')
            ->willReturn($mockResponse);

        $response = $this->controller->showRegister();

        $this->assertSame($mockResponse, $response);
    }

    public function testRegisterFailsWhenTurnstileValidationFails(): void
    {
        // Mock request with missing Turnstile token
        $this->request->method('getParsedBody')->willReturn([]);

        $mockResponse = $this->createMock(ResponseInterface::class);
        
        $this->view->expects($this->once())
            ->method('render')
            ->with('login/viewRegisterAccount', [])
            ->willReturn($mockResponse);

        $response = $this->controller->register();

        $this->assertSame($mockResponse, $response);
        // Check that error flash message was set
        $this->assertEquals('Kunde inte validera recaptcha. Försök igen.', Session::get('flash_message')['message']);
    }

    public function testRegisterFailsWhenUserServiceReturnsError(): void
    {
        // Create a mock controller that bypasses Turnstile validation
        $mockController = $this->getMockBuilder(RegistrationController::class)
            ->setConstructorArgs([
                $this->urlGenerator,
                $this->request,
                $this->logger,
                $this->container,
                $this->turnstileSecret,
                $this->userAuthService,
                $this->view
            ])
            ->onlyMethods(['validateRecaptcha'])
            ->getMock();

        $mockController->method('validateRecaptcha')->willReturn(true);

        // Mock request with form data
        $formData = ['email' => 'test@example.com', 'password' => 'test123'];
        $this->request->method('getParsedBody')->willReturn($formData);

        // Mock user service failure
        $this->userAuthService->expects($this->once())
            ->method('registerUser')
            ->with($formData)
            ->willReturn(['success' => false, 'message' => 'Email not found']);

        $this->urlGenerator->expects($this->once())
            ->method('createUrl')
            ->with('show-register')
            ->willReturn('/auth/register');

        $response = $mockController->register();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testRegisterSucceedsAndRendersLoginView(): void
    {
        // Create a mock controller that bypasses Turnstile validation
        $mockController = $this->getMockBuilder(RegistrationController::class)
            ->setConstructorArgs([
                $this->urlGenerator,
                $this->request,
                $this->logger,
                $this->container,
                $this->turnstileSecret,
                $this->userAuthService,
                $this->view
            ])
            ->onlyMethods(['validateRecaptcha'])
            ->getMock();

        $mockController->method('validateRecaptcha')->willReturn(true);

        // Mock request with form data
        $formData = ['email' => 'test@example.com', 'password' => 'test123'];
        $this->request->method('getParsedBody')->willReturn($formData);

        // Mock user service success
        $this->userAuthService->expects($this->once())
            ->method('registerUser')
            ->with($formData)
            ->willReturn(['success' => true]);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('login/viewLogin')
            ->willReturn($mockResponse);

        $response = $mockController->register();

        $this->assertSame($mockResponse, $response);
    }

    public function testActivateSucceedsAndRedirectsToLogin(): void
    {
        $token = 'valid-activation-token';
        $params = ['token' => $token];

        $this->userAuthService->expects($this->once())
            ->method('activateAccount')
            ->with($token)
            ->willReturn(['success' => true]);

        $this->urlGenerator->expects($this->once())
            ->method('createUrl')
            ->with('login')
            ->willReturn('/login');

        $response = $this->controller->activate($this->request, $params);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testActivateFailsAndRedirectsToLoginWithError(): void
    {
        $token = 'invalid-activation-token';
        $params = ['token' => $token];

        $this->userAuthService->expects($this->once())
            ->method('activateAccount')
            ->with($token)
            ->willReturn(['success' => false, 'message' => 'Invalid token']);

        $this->urlGenerator->expects($this->once())
            ->method('createUrl')
            ->with('login')
            ->willReturn('/login');

        $response = $this->controller->activate($this->request, $params);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        Session::destroy();
    }
}