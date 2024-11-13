<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\MedlemController;
use App\Application;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use PDO;
use PDOStatement;

class MedlemControllerTest extends TestCase
{
    private $app;
    private $request;
    private $controller;
    private $conn;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->conn = $this->createMock(PDO::class);

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

        // Mock the getAppDir method to return a string path
        $this->app->method('getAppDir')->willReturn('/path/to/app');

        // Mock the config values needed by MailAliasService
        $this->app->method('getConfig')
            ->willReturnMap([
                ['SMARTEREMAIL_ENABLED', '1'],
                ['SMARTEREMAIL_ALIASNAME', 'a_test_alias'],
                ['SMARTEREMAIL_BASE_URL', 'https://test.example.com'],
                ['SMARTEREMAIL_USERNAME', 'testuser'],
                ['SMARTEREMAIL_PASSWORD', 'testpass']
            ]);

        // Mock Database singleton
        $database = $this->createMock(\App\Utils\Database::class);
        $database->method('getConnection')
            ->willReturn($this->conn);

        // Set up Database singleton
        $reflection = new \ReflectionClass(\App\Utils\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, $database);

        $this->controller = new MedlemController($this->app, $this->request);
    }

    protected function tearDown(): void
    {
        // Reset Database singleton
        $reflection = new \ReflectionClass(\App\Utils\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    public function testUpdateEmailAliasWhenEnabled(): void
    {
        // Mock the mail alias service
        $mailAliasService = $this->createMock(\App\Services\MailAliasService::class);

        $mailAliasService->expects($this->once())
            ->method('updateAlias')
            ->with('a_test_alias', $this->isType('array'));

        // Inject service using reflection
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('mailAliasService');
        $property->setAccessible(true);
        $property->setValue($this->controller, $mailAliasService);

        $this->controller->updateEmailAliases();
    }

    public function testUpdateEmailAliasWhenDisabled(): void
    {
        // Override all config values including the base setup
        $this->app = $this->createMock(Application::class);
        $this->app->method('getAppDir')->willReturn('/path/to/app');
        $this->app->method('getConfig')
            ->willReturn('0');  // Return empty string for all config calls

        // Create a fresh controller with our new app mock
        $this->controller = new MedlemController($this->app, $this->request);

        // Mock the mail alias service
        $mailAliasService = $this->createMock(\App\Services\MailAliasService::class);

        $mailAliasService->expects($this->never())
            ->method('updateAlias');

        // Inject service using reflection
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('mailAliasService');
        $property->setAccessible(true);
        $property->setValue($this->controller, $mailAliasService);

        $this->controller->updateEmailAliases();
    }
}
