<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\SeglingController;
use App\Services\SeglingService;
use App\Services\SeglingServiceResult;
use App\Utils\View;
use App\Application;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Exception;

class SeglingControllerTest extends TestCase
{
    private SeglingController $controller;
    private MockObject $request;
    private MockObject $seglingService;
    private MockObject $view;
    private MockObject $router;
    private MockObject $app;

    protected function setUp(): void
    {
        $this->seglingService = $this->createMock(SeglingService::class);
        $this->view = $this->createMock(View::class);
        $this->request = $this->createMock(ServerRequest::class);
        $this->router = $this->createMock(Router::class);
        $this->app = $this->createMock(Application::class);

        // Mock router's getNamedRoute method
        $mockRoute = $this->createMock(\League\Route\Route::class);
        $mockRoute->method('getPath')->willReturnCallback(function ($params = []) {
            return '/segling/new'; // Default for tests
        });
        $this->router->method('getNamedRoute')->willReturn($mockRoute);
        $this->app->method('getRouter')->willReturn($this->router);
        $this->app->method('getAppDir')->willReturn('/path/to/app');

        $this->controller = new SeglingController(
            $this->seglingService,
            $this->view,
            $this->app
        );

        $this->setProtectedProperty($this->controller, 'request', $this->request);
        $this->setProtectedProperty($this->controller, 'app', $this->app);
    }

    public function testListDelegatesServiceAndRendersView(): void
    {
        $seglingData = [['id' => 1, 'skeppslag' => 'Test']];

        $this->seglingService->expects($this->once())
            ->method('getAllSeglingar')
            ->willReturn($seglingData);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('viewSegling', [
                'title' => 'Bokningslista',
                'newAction' => '/segling/new',
                'items' => $seglingData
            ])
            ->willReturn($mockResponse);

        $result = $this->controller->list();
        $this->assertSame($mockResponse, $result);
    }

    public function testEditDelegatesServiceAndRendersView(): void
    {
        $editData = [
            'segling' => (object) ['id' => 1, 'skeppslag' => 'Test'],
            'roles' => [],
            'allaSkeppare' => [],
            'allaBatsman' => [],
            'allaKockar' => []
        ];

        $this->seglingService->expects($this->once())
            ->method('getSeglingEditData')
            ->with(1)
            ->willReturn($editData);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('viewSeglingEdit', $this->isType('array'))
            ->willReturn($mockResponse);

        $result = $this->controller->edit($this->request, ['id' => '1']);
        $this->assertSame($mockResponse, $result);
    }

    public function testEditHandlesServiceException(): void
    {
        $this->seglingService->expects($this->once())
            ->method('getSeglingEditData')
            ->with(999)
            ->willThrowException(new Exception('Segling not found'));

        $result = $this->controller->edit($this->request, ['id' => '999']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testSaveDelegatesServiceAndRedirects(): void
    {
        $postData = [
            'startdat' => '2024-01-01',
            'slutdat' => '2024-01-02',
            'skeppslag' => 'Test',
            'kommentar' => 'Test comment'
        ];
        $successResult = new SeglingServiceResult(true, 'Segling uppdaterad!', 'segling-list');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $this->seglingService->expects($this->once())
            ->method('updateSegling')
            ->with(1, $postData)
            ->willReturn($successResult);

        $result = $this->controller->save($this->request, ['id' => '1']);
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testSaveHandlesServiceFailure(): void
    {
        $postData = ['invalid' => 'data'];
        $failureResult = new SeglingServiceResult(false, 'Validation failed');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $this->seglingService->expects($this->once())
            ->method('updateSegling')
            ->with(1, $postData)
            ->willReturn($failureResult);

        $result = $this->controller->save($this->request, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals('application/json', $result->getHeader('Content-Type')[0]);
    }

    public function testDeleteDelegatesServiceAndRedirects(): void
    {
        $successResult = new SeglingServiceResult(true, 'Seglingen är nu borttagen!', 'segling-list');

        $this->seglingService->expects($this->once())
            ->method('deleteSegling')
            ->with(1)
            ->willReturn($successResult);

        $result = $this->controller->delete($this->request, ['id' => '1']);
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testDeleteHandlesServiceFailure(): void
    {
        $failureResult = new SeglingServiceResult(false, 'Kunde inte ta bort seglingen', 'segling-list');

        $this->seglingService->expects($this->once())
            ->method('deleteSegling')
            ->with(1)
            ->willReturn($failureResult);

        $result = $this->controller->delete($this->request, ['id' => '1']);
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testShowCreateRendersView(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('viewSeglingNew', [
                'title' => 'Skapa ny segling',
                'formUrl' => '/segling/new'
            ])
            ->willReturn($mockResponse);

        $result = $this->controller->showCreate();
        $this->assertSame($mockResponse, $result);
    }

    public function testCreateDelegatesServiceAndRedirects(): void
    {
        $postData = [
            'startdat' => '2024-01-01',
            'slutdat' => '2024-01-02',
            'skeppslag' => 'Test',
            'kommentar' => 'Test comment'
        ];
        $successResult = new SeglingServiceResult(true, 'Seglingen är nu skapad!', 'segling-edit', 123);

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $this->seglingService->expects($this->once())
            ->method('createSegling')
            ->with($postData)
            ->willReturn($successResult);

        $result = $this->controller->create();
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testCreateHandlesServiceFailure(): void
    {
        $postData = ['startdat' => '', 'slutdat' => '2024-01-02', 'skeppslag' => 'Test'];
        $failureResult = new SeglingServiceResult(false, 'Indata saknades', 'segling-show-create');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $this->seglingService->expects($this->once())
            ->method('createSegling')
            ->with($postData)
            ->willReturn($failureResult);

        $result = $this->controller->create();
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }



    public function testSaveMedlemDelegatesServiceAndReturnsJson(): void
    {
        $postData = [
            'segling_id' => '1',
            'segling_person' => '2',
            'segling_roll' => '3'
        ];
        $successResult = new SeglingServiceResult(true, 'Medlem tillagd på segling');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $this->seglingService->expects($this->once())
            ->method('addMemberToSegling')
            ->with($postData)
            ->willReturn($successResult);

        $result = $this->controller->saveMedlem();
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals('application/json', $result->getHeader('Content-Type')[0]);
    }

    public function testSaveMedlemHandlesServiceFailure(): void
    {
        $postData = ['segling_id' => '1'];
        $failureResult = new SeglingServiceResult(false, 'Missing input');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $this->seglingService->expects($this->once())
            ->method('addMemberToSegling')
            ->with($postData)
            ->willReturn($failureResult);

        $result = $this->controller->saveMedlem();
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }





    public function testDeleteMedlemFromSeglingDelegatesServiceAndReturnsJson(): void
    {
        $data = ['segling_id' => '1', 'medlem_id' => '2'];
        $successResult = new SeglingServiceResult(true, 'Member removed successfully');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

        $this->seglingService->expects($this->once())
            ->method('removeMemberFromSegling')
            ->with($data)
            ->willReturn($successResult);

        $result = $this->controller->deleteMedlemFromSegling();
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals('application/json', $result->getHeader('Content-Type')[0]);
    }

    public function testDeleteMedlemFromSeglingHandlesServiceFailure(): void
    {
        $data = ['segling_id' => '1'];
        $failureResult = new SeglingServiceResult(false, 'Invalid data');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

        $this->seglingService->expects($this->once())
            ->method('removeMemberFromSegling')
            ->with($data)
            ->willReturn($failureResult);

        $result = $this->controller->deleteMedlemFromSegling();
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    private function setProtectedProperty(object $object, string $property, $value): void
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }
}
