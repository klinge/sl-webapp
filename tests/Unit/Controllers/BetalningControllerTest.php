<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\BetalningController;
use App\Models\BetalningRepository;
use App\Models\MedlemRepository;
use App\Application;
use App\Utils\View;
use App\Utils\Email;
use Psr\Http\Message\ServerRequestInterface;
use PDO;
use PDOStatement;

class BetalningControllerTest extends TestCase
{
    private $app;
    private $request;
    private $logger;
    private $controller;
    private $conn;
    private $betalningRepo;
    private $medlemRepo;
    private $view;
    private $email;
    private $pdoStatement;

    protected function setUp(): void
    {

        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->logger = $this->createMock(\Monolog\Logger::class);
        $this->view = $this->createMock(View::class);
        $this->betalningRepo = $this->createMock(BetalningRepository::class);
        $this->medlemRepo = $this->createMock(MedlemRepository::class);
        $this->email = $this->createMock(Email::class);

        // Create PDO and statement mocks
        $this->pdoStatement = $this->createMock(PDOStatement::class);
        $this->pdoStatement->method('execute')->willReturn(true);

        $this->conn = $this->createMock(PDO::class);
        $this->conn->method('prepare')->willReturn($this->pdoStatement);

        // Mock the getAppDir method
        $this->app->method('getAppDir')->willReturn('/path/to/app');

        $this->controller = new BetalningController(
            $this->app,
            $this->request,
            $this->logger,
            $this->conn,
            $this->betalningRepo,
            $this->medlemRepo,
            $this->email
        );

        $this->setProtectedProperty($this->controller, 'view', $this->view);
    }

    private function getTestMemberData(): array
    {
        return [
            'id' => 1,
            'fornamn' => 'Test',
            'efternamn' => 'Person',
            'email' => 'test@example.com',
            'godkant_gdpr' => 1,
            'pref_kommunikation' => 1,
            'foretag' => 0,
            'standig_medlem' => 0,
            'skickat_valkomstbrev' => 0,
            'isAdmin' => 0,
            'created_at' => '2023-01-01',
            'updated_at' => '2023-01-01'
        ];
    }

    protected function tearDown(): void
    {
        // Reset Database singleton
        $reflection = new \ReflectionClass(\App\Utils\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    public function testListRendersCorrectView(): void
    {
        $testPayments = [
            ['id' => 1, 'belopp' => 100, 'namn' => 'Test Person'],
            ['id' => 2, 'belopp' => 200, 'namn' => 'Another Person']
        ];

        $this->betalningRepo->expects($this->once())
            ->method('getAllWithName')
            ->willReturn($testPayments);

        $expectedViewData = [
            'title' => 'Betalningslista',
            'items' => $testPayments
        ];

        $this->view->expects($this->once())
            ->method('render')
            ->with('viewBetalning', $expectedViewData);

        $this->controller->list();
    }

    public function testGetMedlemBetalningSuccess(): void
    {
        $params = ['id' => 1];
        $testPayments = [
            ['id' => 1, 'belopp' => 100, 'namn' => 'Test Person'],
            ['id' => 2, 'belopp' => 200, 'namn' => 'Test Person']
        ];

        // Mock medlem object
        $mockMedlem = $this->createMock(\App\Models\Medlem::class);
        $mockMedlem->method('getNamn')->willReturn('Test Person');
        
        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($mockMedlem);

        //Used by GetMedlemBetalning
        $this->pdoStatement->method('fetch')->willReturn($this->getTestMemberData());

        $this->betalningRepo->expects($this->once())
            ->method('getBetalningForMedlem')
            ->willReturn($testPayments);

        $expectedViewData = [
            'success' => true,
            'title' => 'Betalningar för: Test Person',
            'items' => $testPayments
        ];
        $this->view->expects($this->once())
            ->method('render')
            ->with('viewBetalning', $expectedViewData);

        $this->controller->getMedlemBetalning($params);
    }

    public function testGetMedlemBetalningNoResults(): void
    {
        $params = ['id' => 1];

        // Mock medlem object
        $mockMedlem = $this->createMock(\App\Models\Medlem::class);
        $mockMedlem->method('getNamn')->willReturn('Test Person');
        
        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($mockMedlem);

        // Set up member data for the Medlem object
        $this->pdoStatement->method('fetch')->willReturn($this->getTestMemberData());

        // Return empty array for betalningar
        $this->betalningRepo->expects($this->once())
            ->method('getBetalningForMedlem')
            ->willReturn([]);

        $expectedViewData = [
            'success' => false,
            'title' => 'Inga betalningar hittades'
        ];

        $this->view->expects($this->once())
            ->method('render')
            ->with('viewBetalning', $expectedViewData);

        $this->controller->getMedlemBetalning($params);
    }

    public function testNoWelcomeEmailWhenDisabled(): void
    {
        $this->setProtectedValue($this->controller, 'welcomeEmailEnabled', "0");

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('sendWelcomeEmailOnFirstPayment');
        $method->setAccessible(true);

        // Test the method directly
        $result = $method->invoke($this->controller, 1);

        $this->assertFalse($result);
    }

    private function setProtectedProperty(object $protectedClass, string $property, object $objectToInject): void
    {
        // Inject service using reflection
        $reflection = new \ReflectionClass($protectedClass);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($protectedClass, $objectToInject);
    }

    private function setProtectedValue(object $protectedClass, string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass($protectedClass);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($protectedClass, $value);
    }
}
