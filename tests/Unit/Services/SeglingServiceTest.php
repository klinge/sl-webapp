<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\SeglingService;
use App\Services\SeglingServiceResult;
use App\Models\SeglingRepository;
use App\Models\BetalningRepository;
use App\Models\MedlemRepository;
use App\Models\Roll;
use App\Models\Segling;
use App\Utils\Session;
use Monolog\Logger;
use Exception;
use PDOException;

class SeglingServiceTest extends TestCase
{
    private SeglingService $service;
    private $seglingRepo;
    private $betalningRepo;
    private $medlemRepo;
    private $roll;
    private $logger;

    protected function setUp(): void
    {
        $this->seglingRepo = $this->createMock(SeglingRepository::class);
        $this->betalningRepo = $this->createMock(BetalningRepository::class);
        $this->medlemRepo = $this->createMock(MedlemRepository::class);
        $this->roll = $this->createMock(Roll::class);
        $this->logger = $this->createMock(Logger::class);

        $this->service = new SeglingService(
            $this->seglingRepo,
            $this->betalningRepo,
            $this->medlemRepo,
            $this->roll,
            $this->logger
        );

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function testGetAllSeglingar(): void
    {
        $expectedSeglingar = [
            ['id' => 1, 'skeppslag' => 'Test 1'],
            ['id' => 2, 'skeppslag' => 'Test 2']
        ];

        $this->seglingRepo->expects($this->once())
            ->method('getAllWithDeltagare')
            ->willReturn($expectedSeglingar);

        $result = $this->service->getAllSeglingar();

        $this->assertEquals($expectedSeglingar, $result);
    }

    public function testGetSeglingEditDataSuccess(): void
    {
        $seglingId = 1;
        $mockSegling = new Segling(
            id: 1,
            start_dat: '2024-01-01',
            skeppslag: 'Test Segling',
            deltagare: [['medlem_id' => 1, 'namn' => 'Test Person']]
        );

        $this->seglingRepo->expects($this->once())
            ->method('getByIdWithDeltagare')
            ->with($seglingId)
            ->willReturn($mockSegling);

        $this->betalningRepo->expects($this->once())
            ->method('memberHasPayed')
            ->with(1, 2024)
            ->willReturn(true);

        $this->roll->expects($this->once())
            ->method('getAll')
            ->willReturn([['id' => 1, 'name' => 'Captain']]);

        $this->medlemRepo->expects($this->exactly(3))
            ->method('findMembersByRollName')
            ->willReturn([]);

        $result = $this->service->getSeglingEditData($seglingId);

        $this->assertArrayHasKey('segling', $result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('allaSkeppare', $result);
        $this->assertArrayHasKey('allaBatsman', $result);
        $this->assertArrayHasKey('allaKockar', $result);
        $this->assertEquals($mockSegling, $result['segling']);
    }

    public function testGetSeglingEditDataNotFound(): void
    {
        $seglingId = 999;

        $this->seglingRepo->expects($this->once())
            ->method('getByIdWithDeltagare')
            ->with($seglingId)
            ->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Segling not found');

        $this->service->getSeglingEditData($seglingId);
    }

    public function testUpdateSeglingSuccess(): void
    {
        $seglingId = 1;
        $postData = [
            'startdat' => '2024-01-01',
            'slutdat' => '2024-01-02',
            'skeppslag' => 'Updated Test',
            'kommentar' => 'Updated comment'
        ];

        $this->seglingRepo->expects($this->once())
            ->method('update')
            ->with($seglingId, $this->isType('array'))
            ->willReturn(true);

        $result = $this->service->updateSegling($seglingId, $postData);

        $this->assertInstanceOf(SeglingServiceResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('Segling uppdaterad!', $result->message);
        $this->assertEquals('segling-list', $result->redirectRoute);
    }

    public function testUpdateSeglingFailure(): void
    {
        $seglingId = 1;
        $postData = ['startdat' => '2024-01-01'];

        $this->seglingRepo->expects($this->once())
            ->method('update')
            ->willReturn(false);

        $result = $this->service->updateSegling($seglingId, $postData);

        $this->assertFalse($result->success);
        $this->assertEquals('Kunde inte uppdatera seglingen. Försök igen.', $result->message);
    }

    public function testDeleteSeglingSuccess(): void
    {
        $seglingId = 1;
        Session::set('user_id', 123);

        $this->seglingRepo->expects($this->once())
            ->method('delete')
            ->with($seglingId)
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->service->deleteSegling($seglingId);

        $this->assertTrue($result->success);
        $this->assertEquals('Seglingen är nu borttagen!', $result->message);
        $this->assertEquals('segling-list', $result->redirectRoute);
    }

    public function testDeleteSeglingFailure(): void
    {
        $seglingId = 1;
        Session::set('user_id', 123);

        $this->seglingRepo->expects($this->once())
            ->method('delete')
            ->willReturn(false);

        $this->logger->expects($this->once())
            ->method('warning');

        $result = $this->service->deleteSegling($seglingId);

        $this->assertFalse($result->success);
        $this->assertEquals('Kunde inte ta bort seglingen. Försök igen.', $result->message);
    }

    public function testCreateSeglingSuccess(): void
    {
        $postData = [
            'startdat' => '2024-01-01',
            'slutdat' => '2024-01-02',
            'skeppslag' => 'New Segling',
            'kommentar' => 'New comment'
        ];

        $this->seglingRepo->expects($this->once())
            ->method('create')
            ->with($this->isType('array'))
            ->willReturn(123);

        $result = $this->service->createSegling($postData);

        $this->assertTrue($result->success);
        $this->assertEquals('Seglingen är nu skapad!', $result->message);
        $this->assertEquals('segling-edit', $result->redirectRoute);
        $this->assertEquals(123, $result->seglingId);
    }

    public function testCreateSeglingMissingData(): void
    {
        $postData = ['startdat' => '', 'slutdat' => '2024-01-02', 'skeppslag' => 'Test'];

        $result = $this->service->createSegling($postData);

        $this->assertFalse($result->success);
        $this->assertEquals('Indata saknades. Kunde inte spara seglingen. Försök igen.', $result->message);
        $this->assertEquals('segling-show-create', $result->redirectRoute);
    }

    public function testCreateSeglingFailure(): void
    {
        $postData = [
            'startdat' => '2024-01-01',
            'slutdat' => '2024-01-02',
            'skeppslag' => 'Test',
            'kommentar' => 'Test'
        ];

        $this->seglingRepo->expects($this->once())
            ->method('create')
            ->willReturn(null);

        $result = $this->service->createSegling($postData);

        $this->assertFalse($result->success);
        $this->assertEquals('Kunde inte spara till databas. Försök igen.', $result->message);
    }

    public function testAddMemberToSeglingSuccess(): void
    {
        $postData = [
            'segling_id' => '1',
            'segling_person' => '2',
            'segling_roll' => '3'
        ];

        $this->seglingRepo->expects($this->once())
            ->method('isMemberOnSegling')
            ->with(1, 2)
            ->willReturn(false);

        $this->seglingRepo->expects($this->once())
            ->method('addMemberToSegling')
            ->with(1, 2, 3)
            ->willReturn(true);

        $result = $this->service->addMemberToSegling($postData);

        $this->assertTrue($result->success);
        $this->assertEquals('Medlem tillagd på segling', $result->message);
    }

    public function testAddMemberToSeglingMissingInput(): void
    {
        $postData = ['segling_id' => '1'];

        $result = $this->service->addMemberToSegling($postData);

        $this->assertFalse($result->success);
        $this->assertEquals('Missing input', $result->message);
    }

    public function testAddMemberToSeglingAlreadyExists(): void
    {
        $postData = [
            'segling_id' => '1',
            'segling_person' => '2'
        ];

        $this->seglingRepo->expects($this->once())
            ->method('isMemberOnSegling')
            ->with(1, 2)
            ->willReturn(true);

        $result = $this->service->addMemberToSegling($postData);

        $this->assertFalse($result->success);
        $this->assertEquals('Medlemmen är redan tillagd på seglingen.', $result->message);
    }

    public function testAddMemberToSeglingPDOException(): void
    {
        $postData = [
            'segling_id' => '1',
            'segling_person' => '2'
        ];

        $this->seglingRepo->expects($this->once())
            ->method('isMemberOnSegling')
            ->willReturn(false);

        $this->seglingRepo->expects($this->once())
            ->method('addMemberToSegling')
            ->willThrowException(new PDOException('Database error'));

        $result = $this->service->addMemberToSegling($postData);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('PDO error', $result->message);
    }

    public function testRemoveMemberFromSeglingSuccess(): void
    {
        $data = ['segling_id' => '1', 'medlem_id' => '2'];
        Session::set('user_id', 123);

        $this->seglingRepo->expects($this->once())
            ->method('removeMemberFromSegling')
            ->with(1, 2)
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->service->removeMemberFromSegling($data);

        $this->assertTrue($result->success);
        $this->assertEquals('Member removed successfully', $result->message);
    }

    public function testRemoveMemberFromSeglingInvalidData(): void
    {
        $data = ['segling_id' => '1'];

        $this->logger->expects($this->once())
            ->method('warning');

        $result = $this->service->removeMemberFromSegling($data);

        $this->assertFalse($result->success);
        $this->assertEquals('Invalid data', $result->message);
    }

    public function testRemoveMemberFromSeglingFailure(): void
    {
        $data = ['segling_id' => '1', 'medlem_id' => '2'];

        $this->seglingRepo->expects($this->once())
            ->method('removeMemberFromSegling')
            ->willReturn(false);

        $this->logger->expects($this->once())
            ->method('warning');

        $result = $this->service->removeMemberFromSegling($data);

        $this->assertFalse($result->success);
        $this->assertEquals('Deletion failed', $result->message);
    }
}
