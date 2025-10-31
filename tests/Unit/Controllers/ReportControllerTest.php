<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\ReportController;
use App\Services\UrlGeneratorService;
use App\Utils\View;
use App\Models\MedlemRepository;
use Psr\Http\Message\ServerRequestInterface;
use Monolog\Logger;
use League\Container\Container;
use PDOStatement;

class ReportControllerTest extends TestCase
{
    private $urlGenerator;
    private $request;
    private $controller;
    private $conn;
    private $logger;
    private $container;
    private $view;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorService::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->conn = $this->createMock(\PDO::class);
        $this->logger = $this->createMock(Logger::class);
        $this->container = $this->createMock(Container::class);
        $this->view = $this->createMock(View::class);

        // Mock Database singleton
        $database = $this->createMock(\App\Utils\Database::class);
        $database->method('getConnection')
            ->willReturn($this->conn);

        // Set up Database singleton
        $reflection = new \ReflectionClass(\App\Utils\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, $database);

        $this->controller = new ReportController(
            $this->urlGenerator,
            $this->request,
            $this->logger,
            $this->container,
            $this->conn,
            $this->view
        );
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
        // Set expectations
        $this->view->expects($this->once())
            ->method('render')
            ->with(
                'reports/viewRapporter',
                ['title' => 'Rapporter']
            );

        $this->controller->show();
    }

    public function testShowPaymentReportSuccess(): void
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

        // Set expectations
        $this->view->expects($this->once())
            ->method('render')
            ->with(
                'reports/viewReportResults',
                [
                    'title' => 'Rapport: Ej gottstående medlemmar',
                    'items' => [['id' => 1, 'name' => 'Test Member']]
                ]
            );

        $this->controller->showPaymentReport();
    }

    public function testShowPaymentReportThrowsExceptionForInvalidYearParam(): void
    {
        $this->request->method('getParsedBody')
            ->willReturn(['yearRadio' => 5]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid year parameter. Must be 1, 2 or 3.');

        $this->controller->showPaymentReport();
    }

    public function testShowMemberEmails(): void
    {
        // Mock MedlemRepository
        $medlemRepoMock = $this->createMock(MedlemRepository::class);
        $medlemRepoMock->method('getEmailForActiveMembers')
            ->willReturn([
                ['email' => 'test1@example.com'],
                ['email' => 'test2@example.com']
            ]);

        // Set expectations
        $this->view->expects($this->once())
            ->method('render')
            ->with(
                'reports/viewReportResults',
                [
                    'title' => 'Rapport: Email till aktiva medlemmar',
                    'items' => [
                        ['email' => 'test1@example.com'],
                        ['email' => 'test2@example.com']
                    ]
                ]
            );

        // Inject mock using reflection
        $reflection = new \ReflectionClass($this->controller);

        $medlemRepoProperty = $reflection->getProperty('medlemRepo');
        $medlemRepoProperty->setAccessible(true);
        $medlemRepoProperty->setValue($this->controller, $medlemRepoMock);

        $this->controller->showMemberEmails();
    }
}
