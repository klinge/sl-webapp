<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\BetalningController;
use App\Models\BetalningRepository;
use App\Application;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use PDO;
use PDOStatement;

class BetalningControllerTest extends TestCase
{
    private $app;
    private $request;
    private $controller;
    private $conn;
    private $betalningRepo;
    private $view;

    protected function setUp(): void
    {

        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->conn = $this->createMock(PDO::class);
        $this->view = $this->createMock(View::class);
        $this->betalningRepo = $this->createMock(BetalningRepository::class);

        // Mock PDO prepare and statement
        $pdoStatement = $this->createMock(PDOStatement::class);
        $pdoStatement->method('execute')->willReturn(true);
        $pdoStatement->method('fetchAll')->willReturn([
            ['email' => 'test1@example.com'],
            ['email' => 'test2@example.com']
        ]);

        $this->conn->method('prepare')->willReturn($pdoStatement);

        // Mock the getAppDir method
        $this->app->method('getAppDir')->willReturn('/path/to/app');

        // Mock Database singleton
        $database = $this->createMock(\App\Utils\Database::class);
        $database->method('getConnection')
            ->willReturn($this->conn);

        // Set up Database singleton
        $reflection = new \ReflectionClass(\App\Utils\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, $database);

        $this->controller = new BetalningController($this->app, $this->request);

        $this->setProtectedProperty($this->controller, 'betalningRepo', $this->betalningRepo);
        $this->setProtectedProperty($this->controller, 'view', $this->view);
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

    private function setProtectedProperty(object $protectedClass, string $property, object $objectToInject): void
    {
        // Inject service using reflection
        $reflection = new \ReflectionClass($protectedClass);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($protectedClass, $objectToInject);
    }
}
