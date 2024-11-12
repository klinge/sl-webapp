<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\ReportController;
use App\Application;
use App\Utils\View;
use App\Models\MedlemRepository;
use Psr\Http\Message\ServerRequestInterface;
use PDO;
use PDOStatement;

class ReportControllerTest extends TestCase
{
    private $app;
    private $request;
    private $controller;
    private $conn;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->conn = $this->createMock(\PDO::class);

        //Mock the getAppDir method to return a string path
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

        $this->controller = new ReportController($this->app, $this->request);
    }

    protected function tearDown(): void
    {
        // Reset Database singleton
        $reflection = new \ReflectionClass(\App\Utils\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    public function testShow(): void
    {
        // Create mock for View
        $viewMock = $this->createMock(View::class);

        // Set expectations
        $viewMock->expects($this->once())
            ->method('render')
            ->with(
                'reports/viewRapporter',
                ['title' => 'Rapporter']
            );

        // Inject mock using reflection
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('view');
        $property->setAccessible(true);
        $property->setValue($this->controller, $viewMock);

        $this->controller->show();
    }

    public function testShowPaymentReport(): void
    {
        // Mock request parsed body
        $this->request->method('getParsedBody')
            ->willReturn(['yearRadio' => 3]);

        // Mock PDO statement
        $pdoStatement = $this->createMock(PDOStatement::class);
        $pdoStatement->method('execute')->willReturn(true);
        $pdoStatement->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'Test Member']
        ]);

        // Mock PDO prepare
        $this->conn->method('prepare')
            ->willReturn($pdoStatement);

        // Create mock for View
        $viewMock = $this->createMock(View::class);
        $viewMock->expects($this->once())
            ->method('render')
            ->with(
                'reports/viewReportResults',
                [
                    'title' => 'Rapport: Ej gottstående medlemmar',
                    'items' => [['id' => 1, 'name' => 'Test Member']]
                ]
            );

        // Inject mock using reflection
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('view');
        $property->setAccessible(true);
        $property->setValue($this->controller, $viewMock);

        $this->controller->showPaymentReport();
    }

    public function testShowMemberEmails(): void
    {
        // Mock MedlemRepository
        $medlemRepoMock = $this->createMock(MedlemRepository::class);
        $medlemRepoMock->method('getEmailForActiveMembers')
            ->willReturn(['test@example.com']);

        // Create mock for View
        $viewMock = $this->createMock(View::class);
        $viewMock->expects($this->once())
            ->method('render')
            ->with(
                'reports/viewReportResults',
                [
                    'title' => 'Rapport: Email till aktiva medlemmar',
                    'items' => ['test@example.com']
                ]
            );

        // Inject mocks using reflection
        $reflection = new \ReflectionClass($this->controller);

        $viewProperty = $reflection->getProperty('view');
        $viewProperty->setAccessible(true);
        $viewProperty->setValue($this->controller, $viewMock);

        $medlemRepoProperty = $reflection->getProperty('medlemRepo');
        $medlemRepoProperty->setAccessible(true);
        $medlemRepoProperty->setValue($this->controller, $medlemRepoMock);

        $this->controller->showMemberEmails();
    }
}
