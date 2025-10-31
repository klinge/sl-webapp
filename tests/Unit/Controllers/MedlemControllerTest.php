<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\MedlemController;
use App\Services\MedlemService;
use App\Services\MedlemServiceResult;
use App\Application;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use League\Route\Router;
use Monolog\Logger;
use Exception;

class MedlemControllerTest extends TestCase
{
    private MedlemController $controller;
    private $app;
    private $request;
    private $logger;
    private $medlemService;
    private $view;
    private $router;
    private $urlGenerator;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->medlemService = $this->createMock(MedlemService::class);
        $this->view = $this->createMock(View::class);
        $this->router = $this->createMock(Router::class);

        $this->urlGenerator = $this->createMock(\App\Services\UrlGeneratorService::class);

        $this->controller = new MedlemController(
            $this->medlemService,
            $this->view,
            $this->urlGenerator
        );

        $this->setProtectedProperty($this->controller, 'request', $this->request);
    }

    private function mockCreateUrl(string $route, array $params = []): void
    {
        $expectedPath = $route === 'medlem-new' ? '/medlem/new' : '/medlem';
        $this->urlGenerator->method('createUrl')->willReturn($expectedPath);
    }

    public function testListAllDelegatesServiceAndRendersView(): void
    {
        $expectedMembers = [['id' => 1, 'name' => 'Test Member']];

        $this->medlemService->expects($this->once())
            ->method('getAllMembers')
            ->willReturn($expectedMembers);

        $this->mockCreateUrl('medlem-new');

        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with(
                'viewMedlem',
                [
                    'title' => 'Medlemmar',
                    'items' => $expectedMembers,
                    'newAction' => '/medlem/new'
                ]
            )
            ->willReturn($mockResponse);

        $response = $this->controller->listAll();

        $this->assertSame($mockResponse, $response);
    }

    public function testListJsonDelegatesServiceAndReturnsJson(): void
    {
        $expectedMembers = [['id' => 1, 'name' => 'Test Member']];

        $this->medlemService->expects($this->once())
            ->method('getAllMembers')
            ->willReturn($expectedMembers);

        $response = $this->controller->listJson();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('application/json', $response->getHeader('Content-Type')[0]);
        $this->assertEquals($expectedMembers, json_decode((string) $response->getBody(), true));
    }


    public function testEditDelegatesServiceAndRendersView(): void
    {
        $memberId = 1;
        $memberData = [
            'medlem' => (object) ['id' => 1, 'name' => 'Test'],
            'roller' => [['id' => 1, 'name' => 'Admin']],
            'seglingar' => ['segling1'],
            'betalningar' => [['id' => 1, 'amount' => 100]]
        ];

        $this->medlemService->expects($this->once())
            ->method('getMemberEditData')
            ->with($memberId)
            ->willReturn($memberData);

        $this->mockCreateUrl('medlem-update');

        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('viewMedlemEdit', $this->isType('array'))
            ->willReturn($mockResponse);

        $response = $this->controller->edit($this->request, ['id' => $memberId]);

        $this->assertSame($mockResponse, $response);
    }

    public function testEditHandlesServiceException(): void
    {
        $memberId = 999;

        $this->medlemService->expects($this->once())
            ->method('getMemberEditData')
            ->with($memberId)
            ->willThrowException(new Exception('Medlem not found'));

        $this->mockCreateUrl('medlem-list');

        $response = $this->controller->edit($this->request, ['id' => $memberId]);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        // Should be a redirect response
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testUpdateDelegatesServiceAndRedirects(): void
    {
        $memberId = 1;
        $postData = ['fornamn' => 'Updated', 'efternamn' => 'Name'];
        $successResult = new MedlemServiceResult(true, 'Member updated!', 'medlem-list');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $this->medlemService->expects($this->once())
            ->method('updateMember')
            ->with($memberId, $postData)
            ->willReturn($successResult);

        $this->mockCreateUrl('medlem-list');

        $response = $this->controller->update($this->request, ['id' => $memberId]);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testUpdateHandlesServiceFailure(): void
    {
        $memberId = 1;
        $postData = ['fornamn' => ''];
        $failureResult = new MedlemServiceResult(false, 'Validation failed', 'medlem-edit');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $this->medlemService->expects($this->once())
            ->method('updateMember')
            ->willReturn($failureResult);

        $this->mockCreateUrl('medlem-edit');

        $response = $this->controller->update($this->request, ['id' => $memberId]);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testShowNewFormDelegatesServiceAndRendersView(): void
    {
        $expectedRoles = [['id' => 1, 'name' => 'Admin']];

        $this->medlemService->expects($this->once())
            ->method('getAllRoles')
            ->willReturn($expectedRoles);

        $this->mockCreateUrl('medlem-create');

        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with(
                'viewMedlemNew',
                [
                    'title' => 'Lägg till medlem',
                    'roller' => $expectedRoles,
                    'formAction' => '/medlem'
                ]
            )
            ->willReturn($mockResponse);

        $response = $this->controller->showNewForm();

        $this->assertSame($mockResponse, $response);
    }

    public function testCreateDelegatesServiceAndRedirects(): void
    {
        $postData = ['fornamn' => 'New', 'efternamn' => 'Member'];
        $successResult = new MedlemServiceResult(true, 'Member created!', 'medlem-list');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $this->medlemService->expects($this->once())
            ->method('createMember')
            ->with($postData)
            ->willReturn($successResult);

        $this->mockCreateUrl('medlem-list');

        $response = $this->controller->create();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testDeleteDelegatesServiceAndRedirects(): void
    {
        $memberId = 1;
        $successResult = new MedlemServiceResult(true, 'Member deleted!', 'medlem-list');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn(['id' => $memberId]);

        $this->medlemService->expects($this->once())
            ->method('deleteMember')
            ->with($memberId)
            ->willReturn($successResult);

        $this->mockCreateUrl('medlem-list');

        $response = $this->controller->delete();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    private function setProtectedProperty(object $object, string $property, $value): void
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }
}
