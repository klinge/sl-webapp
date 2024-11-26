<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\MedlemController;
use App\Application;
use App\Models\MedlemRepository;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use PDO;
use PDOStatement;
use AltoRouter;

class MedlemControllerTest extends TestCase
{
    private $app;
    private $request;
    private $controller;
    private $conn;
    private $medlemRepo;
    private $router;
    private $view;

    protected function setUp(): void
    {

        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->router = $this->createMock(AltoRouter::class);
        $this->conn = $this->createMock(PDO::class);
        $this->view = $this->createMock(View::class);
        $this->medlemRepo = $this->createMock(MedlemRepository::class);

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

        $this->setProtectedProperty($this->controller, 'medlemRepo', $this->medlemRepo);
    }

    protected function tearDown(): void
    {
        // Reset Database singleton
        $reflection = new \ReflectionClass(\App\Utils\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    private function setupBasicMemberData(): array
    {
        return [
            [
                'id' => 1,
                'fornamn' => 'Test',
                'efternamn' => 'Person',
                'fodelsedatum' => '1990-01-01',
                'email' => 'test@example.com',
                'mobil' => '123456789',
                'telefon' => '987654321',
                'adress' => 'Test Street 1',
                'postnummer' => '12345',
                'postort' => 'Test City',
                'kommentar' => 'Test comment',
                'godkant_gdpr' => 1,
                'pref_kommunikation' => 1,
                'foretag' => 0,
                'standig_medlem' => 1,
                'skickat_valkomstbrev' => 0,
                'isAdmin' => 0,
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00'
            ]
        ];
    }

    public function testListAllRendersViewWithCorrectData(): void
    {
        // Mock the expected data from repository
        $expectedMembers = $this->setupBasicMemberData();

        // Setup repository mock to return test data
        $this->medlemRepo->expects($this->once())
            ->method('getAll')
            ->willReturn($expectedMembers);

        // Mock router to return a test URL
        $this->router->method('generate')
            ->with('medlem-new')
            ->willReturn('/medlem/new');

        $this->app->method('getRouter')
            ->willReturn($this->router);

        // Assert view is rendered with correct data
        $this->view->expects($this->once())
            ->method('render')
            ->with(
                'viewMedlem',
                [
                    'title' => 'Medlemmar',
                    'items' => $expectedMembers,
                    'newAction' => '/medlem/new'
                ]
            );

        // Inject mock view into controller
        $this->setProtectedProperty($this->controller, 'view', $this->view);

        // Execute the method
        $this->controller->listAll();
    }

    public function testListJsonOutputsCorrectJsonResponse(): void
    {
        // Mock the expected data from repository
        $expectedMembers = $this->setupBasicMemberData();

        // Setup repository mock to return test data
        $this->medlemRepo->expects($this->once())
            ->method('getAll')
            ->willReturn($expectedMembers);

        // Start output buffering to capture the JSON response
        ob_start();
        $this->controller->listJson();
        $output = ob_get_clean();

        // Verify the output is valid JSON and matches our expected data
        $this->assertJson($output);
        $this->assertEquals($expectedMembers, json_decode($output, true));
    }


    public function testUpdateEmailAliasWhenEnabled(): void
    {
        // Mock the mail alias service
        $mailAliasService = $this->createMock(\App\Services\MailAliasService::class);

        $mailAliasService->expects($this->once())
            ->method('updateAlias')
            ->with('a_test_alias', $this->isType('array'));

        // Inject services using reflection
        $this->setProtectedProperty($this->controller, 'mailAliasService', $mailAliasService);

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

    private function setProtectedProperty(object $protectedClass, string $property, object $objectToInject): void
    {
        // Inject service using reflection
        $reflection = new \ReflectionClass($protectedClass);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($protectedClass, $objectToInject);
    }
}
