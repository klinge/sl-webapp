<?php

namespace Tests\Unit\Controllers\Auth;

use PHPUnit\Framework\TestCase;
use App\Services\UrlGeneratorService;
use App\Controllers\Auth\PasswordController;
use App\Utils\View;
use App\Utils\Email;
use App\Utils\Session;
use App\Services\Auth\UserAuthenticationService;
use Psr\Http\Message\ServerRequestInterface;
use League\Container\Container;

class PasswordControllerTest extends TestCase
{
    private $controller;
    private $urlGenerator;
    private $request;
    private $authService;
    private $view;
    private $email;
    private $conn;
    private $container;

    protected function setUp(): void
    {
        session_start();
        $this->urlGenerator = $this->createMock(UrlGeneratorService::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->view = $this->createMock(View::class);
        $this->authService = $this->createMock(UserAuthenticationService::class);
        $this->email = $this->createMock(Email::class);
        $this->conn = $this->createMock(\PDO::class);
        $this->container = $this->createMock(Container::class);

        // Mock Database singleton
        $database = $this->createMock(\App\Utils\Database::class);
        $database->method('getConnection')
            ->willReturn($this->conn);
        // Use reflection to set the singleton instance
        $reflection = new \ReflectionClass(\App\Utils\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, $database);


        // Mock the request's getServerParams
        $this->request->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '127.0.0.1']);

        // Mock the logger
        $logger = $this->createMock(\Monolog\Logger::class);

        // Create partial mock of controller, mocking recaptcha validation
        $this->controller = $this->getMockBuilder(PasswordController::class)
            ->setConstructorArgs([
                $this->urlGenerator,
                $this->request,
                $logger,
                $this->container,
                'test-secret-key',
                $this->authService,
                $this->view
            ])
            ->onlyMethods(['validateRecaptcha', 'redirectWithError', 'redirectWithSuccess'])
            ->getMock();

        // Override the view property with our mock
        $controllerReflection = new \ReflectionClass(PasswordController::class);
        $viewProp = $controllerReflection->getProperty('view');
        $viewProp->setAccessible(true);
        $viewProp->setValue($this->controller, $this->view);
    }

    protected function tearDown(): void
    {
        // Clean up session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        // Reset Database singleton
        $reflection = new \ReflectionClass(\App\Utils\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    public function testShowRequestPwdRendersCorrectView(): void
    {
        $this->view->expects($this->once())
            ->method('render')
            ->with('login/viewReqPassword');

        $this->controller->showRequestPwd();
    }

    public function testSendPwdRequestTokenSuccess(): void
    {
        $email = 'test@example.com';

        $this->controller->expects($this->once())
            ->method('validateRecaptcha')
            ->willReturn(true);

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn(['email' => $email]);

        $this->authService->expects($this->once())
            ->method('requestPasswordReset')
            ->with($email)
            ->willReturn(['success' => true]);

        $this->controller->sendPwdRequestToken();

        $this->assertEquals('success', Session::get('flash_message')['type']);
    }

    public function testSendPwdRequestTokenFail(): void
    {
        $email = 'test@example.com';

        $this->controller->expects($this->once())
            ->method('validateRecaptcha')
            ->willReturn(true);

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn(['email' => $email]);

        $this->authService->expects($this->once())
            ->method('requestPasswordReset')
            ->with($email)
            ->willReturn(['success' => false]);

        $this->controller->sendPwdRequestToken();

        $this->assertEquals('error', Session::get('flash_message')['type']);
    }

    public function testShowResetPasswordWithValidToken(): void
    {
        $token = 'valid_token';
        $email = 'test@example.com';

        $this->authService->expects($this->once())
            ->method('validateResetToken')
            ->with($token)
            ->willReturn([
                'success' => true,
                'email' => $email
            ]);

        $expectedViewData = [
            'email' => $email,
            'token' => $token
        ];

        $this->view->expects($this->once())
            ->method('render')
            ->with('login/viewSetNewPassword', $expectedViewData);

        $this->controller->showResetPassword($this->request, ['token' => $token]);
    }

    public function testShowResetPasswordWithInvalidToken(): void
    {
        $token = 'invalid_token';
        $errorMessage = 'Token är ogiltig eller har gått ut';

        $this->authService->expects($this->once())
            ->method('validateResetToken')
            ->with($token)
            ->willReturn([
                'success' => false,
                'message' => $errorMessage
            ]);

        $this->controller->expects($this->once())
            ->method('redirectWithError')
            ->with('show-request-password', 'Token är ogiltig eller har gått ut');

        $this->controller->showResetPassword($this->request, ['token' => 'invalid_token']);
    }

    public function testResetAndSavePasswordSuccess(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'token' => 'valid_token',
            'password' => 'newPassword123',
            'password2' => 'newPassword123'
        ];

        $this->authService->expects($this->once())
            ->method('resetPassword')
            ->with($formData)
            ->willReturn(['success' => true]);

        $this->request->method('getParsedBody')
            ->willReturn($formData);

        $this->controller->expects($this->once())
            ->method('redirectWithSuccess')
            ->with('login', 'Ditt lösenord är uppdaterat. Du kan nu logga in med ditt nya lösenord.');

        $this->controller->resetAndSavePassword();
    }

    public function testResetAndSavePasswordFailure(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'token' => 'invalid_token',
            'password' => 'weak'
        ];

        $this->authService->expects($this->once())
            ->method('resetPassword')
            ->with($formData)
            ->willReturn(['success' => false, 'message' => 'Weak password']);

        $this->request->method('getParsedBody')
            ->willReturn($formData);

        $this->view->expects($this->once())
            ->method('render')
            ->with('login/viewSetNewPassword', [
                'email' => $formData['email'],
                'token' => $formData['token']
            ]);

        $this->controller->resetAndSavePassword();
    }
}
