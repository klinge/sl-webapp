<?php

namespace Tests\Unit\Controllers\Auth;

use PHPUnit\Framework\TestCase;
use App\Application;
use App\Controllers\Auth\LoginController;
use App\Models\MedlemRepository;
use App\Models\Medlem;
use App\Services\Auth\PasswordService;
use App\Utils\View;
use App\Utils\Session;
use Monolog\Logger;
use Psr\Http\Message\ServerRequestInterface;

class LoginControllerTest extends TestCase
{
    private $controller;
    private $app;
    private $request;
    private $medlemRepo;
    private $logger;
    private $passwordService;
    private $view;
    private $conn;
    private $medlemData;

    protected function setUp(): void
    {
        // Start session for tests
        session_start();
        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->medlemRepo = $this->createMock(MedlemRepository::class);
        $this->passwordService = $this->createMock(PasswordService::class);
        $this->conn = $this->createMock(\PDO::class);
        $this->view = $this->createMock(View::class);

        // Create mock router
        $router = $this->createMock(\AltoRouter::class);
        $router->method('generate')
            ->willReturnMap([
                ['home', '/'],
                ['user-home', '/user'],
                ['show-login', '/login']
            ]);

        // Configure app to return our mock router
        $this->app->method('getRouter')
            ->willReturn($router);

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
        $this->app->method('getLogger')
            ->willReturn($this->logger);

        // Mock the config and app directory methods to return test values
        $this->app->method('getConfig')
            ->willReturnMap([
                ['TURNSTILE_SECRET_KEY', 'test-secret-key']
            ]);
        $this->app->method('getAppDir')
            ->willReturn(__DIR__ . '/../../../../App');

        // Mock the request's getServerParams
        $this->request->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '127.0.0.1']);


        // Create partial mock of controller
        $this->controller = $this->getMockBuilder(LoginController::class)
            ->setConstructorArgs([$this->app, $this->request])
            ->onlyMethods(['validateRecaptcha'])
            ->getMock();

        // Set properties on LoginController
        $controllerReflection = new \ReflectionClass(LoginController::class);
        foreach (['view', 'medlemRepo', 'passwordService'] as $property) {
            $prop = $controllerReflection->getProperty($property);
            $prop->setAccessible(true);
            $prop->setValue($this->controller, $this->{$property});
        }

        // Set properties on AuthBaseController
        $parentReflection = new \ReflectionClass(get_parent_class($this->controller));
        foreach (['conn', 'app'] as $property) {
            $prop = $parentReflection->getProperty($property);
            $prop->setAccessible(true);
            $prop->setValue($this->controller, $this->{$property});
        }

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

        // Mock PDO query for Medlem constructor
        $pdoStatement = $this->createMock(\PDOStatement::class);
        $pdoStatement->method('fetch')
            ->willReturn($this->medlemData);

        $this->conn->method('prepare')
            ->willReturn($pdoStatement);

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

        $medlemNotAdminData = $this->medlemData;
        $medlemNotAdminData['isAdmin'] = 0;
        $pdoStatement = $this->createMock(\PDOStatement::class);
        $pdoStatement->method('fetch')
            ->willReturn($medlemNotAdminData);

        $this->conn->method('prepare')
            ->willReturn($pdoStatement);

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

        $pdoStatement = $this->createMock(\PDOStatement::class);
        $pdoStatement->method('fetch')
            ->willReturn($this->medlemData);

        $this->conn->method('prepare')
            ->willReturn($pdoStatement);

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
