<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\SeglingController;
use App\Models\BetalningRepository;
use App\Models\SeglingRepository;
use App\Models\MedlemRepository;
use App\Models\Roll;
use App\Models\Segling;
use App\Utils\View;
use App\Application;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use AltoRouter;
use PDOException;

class SeglingControllerTest extends TestCase
{
    private SeglingController $controller;
    private MockObject $app;
    private MockObject $request;
    private MockObject $logger;
    private MockObject $seglingRepo;
    private MockObject $medlemRepo;
    private MockObject $betalningRepo;
    private MockObject $view;
    private MockObject $roll;
    private MockObject $router;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequest::class);
        $this->logger = $this->createMock(Logger::class);
        $this->seglingRepo = $this->createMock(SeglingRepository::class);
        $this->medlemRepo = $this->createMock(MedlemRepository::class);
        $this->betalningRepo = $this->createMock(BetalningRepository::class);
        $this->view = $this->createMock(View::class);
        $this->roll = $this->createMock(Roll::class);
        $this->router = $this->createMock(AltoRouter::class);

        $this->app->method('getRouter')->willReturn($this->router);

        $this->controller = new SeglingController(
            $this->app,
            $this->request,
            $this->logger,
            $this->seglingRepo,
            $this->medlemRepo,
            $this->betalningRepo,
            $this->view,
            $this->roll
        );
    }

    public function testList(): void
    {
        $seglingData = [['id' => 1, 'skeppslag' => 'Test']];

        $this->seglingRepo->expects($this->once())
            ->method('getAllWithDeltagare')
            ->willReturn($seglingData);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('segling-show-create')
            ->willReturn('/segling/create');

        $this->view->expects($this->once())
            ->method('render')
            ->with('viewSegling', [
                'title' => 'Bokningslista',
                'newAction' => '/segling/create',
                'items' => $seglingData
            ]);

        $this->controller->list();
    }

    public function testEditWithValidId(): void
    {
        $segling = $this->createMock(Segling::class);
        $segling->start_dat = '2024-01-01';
        $segling->method('getDeltagare')->willReturn([
            ['medlem_id' => 1, 'namn' => 'Test']
        ]);

        $this->seglingRepo->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($segling);

        $this->betalningRepo->expects($this->once())
            ->method('memberHasPayed')
            ->with(1, 2024)
            ->willReturn(true);

        $this->roll->expects($this->once())
            ->method('getAll')
            ->willReturn([]);

        $this->medlemRepo->expects($this->exactly(3))
            ->method('getMembersByRollName')
            ->willReturn([]);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('segling-save', ['id' => 1])
            ->willReturn('/segling/save/1');

        $this->view->expects($this->once())
            ->method('render')
            ->with('viewSeglingEdit', $this->isType('array'));

        $result = $this->controller->edit(['id' => '1']);
        $this->assertNull($result);
    }

    public function testEditWithInvalidId(): void
    {
        $this->seglingRepo->expects($this->once())
            ->method('getById')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->edit(['id' => '999']);
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testSaveSuccess(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'startdat' => '2024-01-01',
                'slutdat' => '2024-01-02',
                'skeppslag' => 'Test',
                'kommentar' => 'Test comment'
            ]);

        $this->seglingRepo->expects($this->once())
            ->method('updateSegling')
            ->with(1, $this->isType('array'))
            ->willReturn(true);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('segling-list')
            ->willReturn('/segling');

        $result = $this->controller->save(['id' => '1']);
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testSaveFailure(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'startdat' => '2024-01-01',
                'slutdat' => '2024-01-02',
                'skeppslag' => 'Test',
                'kommentar' => 'Test comment'
            ]);

        $this->seglingRepo->expects($this->once())
            ->method('updateSegling')
            ->willReturn(false);

        $result = $this->controller->save(['id' => '1']);
        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    public function testDeleteSuccess(): void
    {
        $this->seglingRepo->expects($this->once())
            ->method('deleteSegling')
            ->with(1)
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('info');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('segling-list')
            ->willReturn('/segling');

        $result = $this->controller->delete(['id' => '1']);
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testDeleteFailure(): void
    {
        $this->seglingRepo->expects($this->once())
            ->method('deleteSegling')
            ->with(1)
            ->willReturn(false);

        $this->logger->expects($this->once())
            ->method('warning');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('segling-list')
            ->willReturn('/segling');

        $result = $this->controller->delete(['id' => '1']);
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testShowCreate(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with('segling-create')
            ->willReturn('/segling/create');

        $this->view->expects($this->once())
            ->method('render')
            ->with('viewSeglingNew', [
                'title' => 'Skapa ny segling',
                'formUrl' => '/segling/create'
            ]);

        $this->controller->showCreate();
    }

    public function testCreateSuccess(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'startdat' => '2024-01-01',
                'slutdat' => '2024-01-02',
                'skeppslag' => 'Test',
                'kommentar' => 'Test comment'
            ]);

        $this->seglingRepo->expects($this->once())
            ->method('createSegling')
            ->willReturn(123);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('segling-edit', ['id' => 123])
            ->willReturn('/segling/edit/123');

        $result = $this->controller->create();
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testCreateMissingData(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'startdat' => '',
                'slutdat' => '2024-01-02',
                'skeppslag' => 'Test'
            ]);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('segling-show-create')
            ->willReturn('/segling/create');

        $result = $this->controller->create();
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testCreateFailure(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'startdat' => '2024-01-01',
                'slutdat' => '2024-01-02',
                'skeppslag' => 'Test',
                'kommentar' => 'Test comment'
            ]);

        $this->seglingRepo->expects($this->once())
            ->method('createSegling')
            ->willReturn(null);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('segling-show-create')
            ->willReturn('/segling/create');

        $result = $this->controller->create();
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testSaveMedlemSuccess(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'segling_id' => '1',
                'segling_person' => '2',
                'segling_roll' => '3'
            ]);

        $this->seglingRepo->expects($this->once())
            ->method('isMemberOnSegling')
            ->with(1, 2)
            ->willReturn(false);

        $this->seglingRepo->expects($this->once())
            ->method('addMemberToSegling')
            ->with(1, 2, 3)
            ->willReturn(true);

        $result = $this->controller->saveMedlem();
        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    public function testSaveMedlemMissingInput(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn(['segling_id' => '1']);

        $result = $this->controller->saveMedlem();
        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    public function testSaveMedlemAlreadyExists(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'segling_id' => '1',
                'segling_person' => '2'
            ]);

        $this->seglingRepo->expects($this->once())
            ->method('isMemberOnSegling')
            ->with(1, 2)
            ->willReturn(true);

        $result = $this->controller->saveMedlem();
        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    public function testSaveMedlemPDOException(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'segling_id' => '1',
                'segling_person' => '2'
            ]);

        $this->seglingRepo->expects($this->once())
            ->method('isMemberOnSegling')
            ->willReturn(false);

        $this->seglingRepo->expects($this->once())
            ->method('addMemberToSegling')
            ->willThrowException(new PDOException('Database error'));

        $result = $this->controller->saveMedlem();
        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    public function testDeleteMedlemFromSeglingSuccess(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'segling_id' => '1',
                'medlem_id' => '2'
            ]);

        $this->seglingRepo->expects($this->once())
            ->method('removeMemberFromSegling')
            ->with(1, 2)
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->controller->deleteMedlemFromSegling();
        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    public function testDeleteMedlemFromSeglingInvalidData(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn(['segling_id' => '1']);

        $this->logger->expects($this->once())
            ->method('warning');

        $result = $this->controller->deleteMedlemFromSegling();
        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    public function testDeleteMedlemFromSeglingFailure(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'segling_id' => '1',
                'medlem_id' => '2'
            ]);

        $this->seglingRepo->expects($this->once())
            ->method('removeMemberFromSegling')
            ->with(1, 2)
            ->willReturn(false);

        $this->logger->expects($this->once())
            ->method('warning');

        $result = $this->controller->deleteMedlemFromSegling();
        $this->assertInstanceOf(JsonResponse::class, $result);
    }
}
