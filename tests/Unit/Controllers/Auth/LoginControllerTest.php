<?php

namespace Tests\Unit\Controllers\Auth;

use PHPUnit\Framework\TestCase;
use App\Services\UrlGeneratorService;
use App\Controllers\Auth\LoginController;
use App\Models\MedlemRepository;
use App\Models\Medlem;
use App\Services\Auth\PasswordService;
use App\Utils\View;
use App\Utils\Session;
use Monolog\Logger;
use League\Container\Container;
use Psr\Http\Message\ServerRequestInterface;

class LoginControllerTest extends TestCase
{
    private $controller;
    private $urlGenerator;
    private $request;
    private $medlemRepo;
    private $logger;
    private $passwordService;
    private $view;
    private $conn;
    private $container;
    private $medlemData;

    protected function setUp(): void
    {
        // Start session for tests
        Session::start();
        $this->urlGenerator = $this->createMock(UrlGeneratorService::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->medlemRepo = $this->createMock(MedlemRepository::class);
        $this->passwordService = $this->createMock(PasswordService::class);
        $this->conn = $this->createMock(\PDO::class);
        $this->view = $this->createMock(View::class);
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

        // Mock the logger
        $this->logger = $this->createMock(Logger::class);



        // Mock the request's getServerParams
        $this->request->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '127.0.0.1']);


        // Create partial mock of controller that also mocks the constructor dependencies
        $this->controller = $this->getMockBuilder(LoginController::class)
            ->setConstructorArgs([
                $this->urlGenerator,
                $this->request,
                $this->logger,
                $this->container,
                'test-secret-key',
                $this->conn,
                $this->passwordService,
                $this->view
            ])
            ->onlyMethods(['validateRecaptcha'])
            ->getMock();

        // Override the view and medlemRepo properties with our mocks
        $controllerReflection = new \ReflectionClass(LoginController::class);
        $viewProp = $controllerReflection->getProperty('view');
        $viewProp->setAccessible(true);
        $viewProp->setValue($this->controller, $this->view);

        $medlemRepoProp = $controllerReflection->getProperty('medlemRepo');
        $medlemRepoProp->setAccessible(true);
        $medlemRepoProp->setValue($this->controller, $this->medlemRepo);



        $this->medlemData = [
            'id' => 1,
            'email' => 'admin@example.com',
            'fornamn' => 'John',
            'efternamn' => 'Doe',
            'isAdmin' => 1,
            'password' => 'hashedpassword',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-02-02 00:00:00',
            'skickat_valkomstbrev' => 1,
            'standig_medlem' => 0,
            'foretag' => 0,
            'pref_kommunikation' => 1,
            'godkant_gdpr' => 1,
        ];
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

    public function testShowLoginSetsCsrfTokenAndRendersView(): void
    {
        // Configure validateRecaptcha to return false
        $this->controller->method('validateRecaptcha')
            ->willReturn(true);

        $this->view->expects($this->once())
            ->method('render')
            ->with('login/viewLogin');

        $this->controller->showLogin();

        $csrfToken = Session::get('csrf_token');
        $this->assertNotNull($csrfToken);
        $this->assertIsString($csrfToken);
    }

    public function testLoginSuccessForAdminUser(): void
    {
        // Configure validateRecaptcha to return true
        $this->controller->method('validateRecaptcha')
            ->willReturn(true);

        $this->request->method('getParsedBody')
            ->willReturn(['email' => 'admin@example.com', 'password' => 'password123']);

        $this->medlemRepo->expects($this->once())
            ->method('getMemberByEmail')
            ->willReturn(['id' => 1]);

        // Mock repository getById method
        $mockMedlem = new Medlem();
        $mockMedlem->id = 1;
        $mockMedlem->email = 'admin@example.com';
        $mockMedlem->fornamn = 'John';
        $mockMedlem->efternamn = 'Doe';
        $mockMedlem->isAdmin = true;
        $mockMedlem->password = 'hashedpassword';

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($mockMedlem);

        $this->passwordService->expects($this->once())
            ->method('verifyPassword')
            ->willReturn(true);

        $this->controller->login();

        $this->assertTrue(Session::get('is_admin'));
        $this->assertEquals(1, Session::get('user_id'));
        $this->assertEquals('John', Session::get('fornamn'));
    }

    public function testLoginSuccessForRegularUser(): void
    {
        $this->controller->method('validateRecaptcha')
            ->willReturn(true);

        $this->request->method('getParsedBody')
            ->willReturn(['email' => 'user@example.com', 'password' => 'password123']);

        $this->medlemRepo->expects($this->once())
            ->method('getMemberByEmail')
            ->willReturn(['id' => 1]);

        // Mock repository getById method for regular user
        $mockMedlem = new Medlem();
        $mockMedlem->id = 1;
        $mockMedlem->email = 'user@example.com';
        $mockMedlem->fornamn = 'John';
        $mockMedlem->efternamn = 'Doe';
        $mockMedlem->isAdmin = false;
        $mockMedlem->password = 'hashedpassword';

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($mockMedlem);

        $this->passwordService->expects($this->once())
            ->method('verifyPassword')
            ->willReturn(true);

        $this->controller->login();

        $this->assertFalse(Session::get('is_admin'), 'User should not be an admin');
        $this->assertEquals(1, Session::get('user_id'));
        $this->assertEquals('John', Session::get('fornamn'));
    }

    public function testLoginFailsWithInvalidPassword(): void
    {
        $this->controller->method('validateRecaptcha')
            ->willReturn(true);

        $this->request->method('getParsedBody')
            ->willReturn(['email' => 'user@example.com', 'password' => 'wrongpass']);

        $this->medlemRepo->expects($this->once())
            ->method('getMemberByEmail')
            ->willReturn(['id' => 2]);

        // Mock repository getById method
        $mockMedlem = new Medlem();
        $mockMedlem->id = 2;
        $mockMedlem->email = 'user@example.com';
        $mockMedlem->fornamn = 'John';
        $mockMedlem->efternamn = 'Doe';
        $mockMedlem->isAdmin = false;
        $mockMedlem->password = 'hashedpassword';

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with(2)
            ->willReturn($mockMedlem);

        $this->passwordService->expects($this->once())
            ->method('verifyPassword')
            ->willReturn(false);

        $this->controller->login();

        $this->assertEquals('Felaktig e-postadress eller lösenord', Session::get('flash_message')['message']);
    }

    public function testLoginFailsWithInvalidRecaptcha(): void
    {
        // Configure validateRecaptcha to return false
        $this->controller->method('validateRecaptcha')
            ->willReturn(false);


        $this->view->expects($this->once())
            ->method('render')
            ->with('login/viewLogin');

        $this->controller->login();

        $this->assertStringContainsString('inte validera recaptcha', Session::get('flash_message')['message']);
    }

    public function testLoginFailsWithEmptyEmail(): void
    {
        // Configure validateRecaptcha to return true
        $this->controller->method('validateRecaptcha')
            ->willReturn(true);

        $this->request->method('getParsedBody')
            ->willReturn(['email' => '', 'password' => 'password123']);

        $this->view->expects($this->once())
            ->method('render')
            ->with('login/viewLogin');

        $this->controller->login();

        $this->assertStringContainsString('Felaktig e-postadress', Session::get('flash_message')['message']);
    }

    public function testLoginFailsWithNonExistentUser(): void
    {
        // Configure validateRecaptcha to return true
        $this->controller->method('validateRecaptcha')
            ->willReturn(true);

        $this->request->method('getParsedBody')
            ->willReturn(['email' => 'test@example.com', 'password' => 'password123']);

        $this->medlemRepo->expects($this->once())
            ->method('getMemberByEmail')
            ->with('test@example.com')
            ->willReturn(false);

        $this->view->expects($this->once())
            ->method('render')
            ->with('login/viewLogin');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Failed login. Email not existing: test@example.com IP: 127.0.0.1');

        $this->controller->login();
        $this->assertStringContainsString('Felaktig e-postadress', Session::get('flash_message')['message']);
    }

    public function testLogoutDestroysSession(): void
    {
        $this->controller->logout();

        $this->assertNull(Session::get('user_id'));
        $this->assertNull(Session::get('fornamn'));
        $this->assertNull(Session::get('is_admin'));
    }

    private function setPrivateProperty($object, $property, $value): void
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
