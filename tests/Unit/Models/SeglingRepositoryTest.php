<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\SeglingRepository;
use App\Models\Segling;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;

class SeglingRepositoryTest extends TestCase
{
    private SeglingRepository $repository;
    private $mockPdo;
    private $mockLogger;
    private $mockStmt;

    protected function setUp(): void
    {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->mockStmt = $this->createMock(PDOStatement::class);
        
        $this->repository = new SeglingRepository($this->mockPdo, $this->mockLogger);
    }

    public function testGetAll(): void
    {
        $expectedData = [
            [
                'id' => 1,
                'startdatum' => '2024-01-01',
                'slutdatum' => '2024-01-02',
                'skeppslag' => 'Test Crew',
                'kommentar' => 'Test comment',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00'
            ]
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM Segling ORDER BY startdatum DESC')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute');

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        $result = $this->repository->getAll();

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Segling::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('Test Crew', $result[0]->skeppslag);
    }

    public function testGetById(): void
    {
        $expectedData = [
            'id' => 1,
            'startdatum' => '2024-01-01',
            'slutdatum' => '2024-01-02',
            'skeppslag' => 'Test Crew',
            'kommentar' => 'Test comment',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00'
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM Segling WHERE id = ?')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('bindParam')
            ->with(1, 1, PDO::PARAM_INT);

        $this->mockStmt->expects($this->once())
            ->method('execute');

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        $result = $this->repository->getById(1);

        $this->assertInstanceOf(Segling::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test Crew', $result->skeppslag);
    }

    public function testGetByIdNotFound(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $result = $this->repository->getById(999);

        $this->assertNull($result);
    }

    public function testCreate(): void
    {
        $data = [
            'startdat' => '2024-01-01',
            'slutdat' => '2024-01-02',
            'skeppslag' => 'New Crew',
            'kommentar' => 'New comment'
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('INSERT INTO Segling (startdatum, slutdatum, skeppslag, kommentar) VALUES (:startdat, :slutdat, :skeppslag, :kommentar)')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStmt->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $this->mockPdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('123');

        $result = $this->repository->create($data);

        $this->assertEquals(123, $result);
    }

    public function testCreateFailure(): void
    {
        $data = ['startdat' => '2024-01-01', 'slutdat' => '2024-01-02', 'skeppslag' => 'Test'];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStmt->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        $result = $this->repository->create($data);

        $this->assertNull($result);
    }

    public function testUpdate(): void
    {
        $data = [
            'startdat' => '2024-01-01',
            'slutdat' => '2024-01-02',
            'skeppslag' => 'Updated Crew',
            'kommentar' => 'Updated comment'
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStmt->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->repository->update(1, $data);

        $this->assertTrue($result);
    }

    public function testUpdateFailure(): void
    {
        $data = ['startdat' => '2024-01-01', 'slutdat' => '2024-01-02', 'skeppslag' => 'Test'];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStmt->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        $result = $this->repository->update(1, $data);

        $this->assertFalse($result);
    }

    public function testDelete(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('DELETE FROM Segling WHERE id = ?')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStmt->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->repository->delete(1);

        $this->assertTrue($result);
    }

    public function testDeleteFailure(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStmt->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        $result = $this->repository->delete(1);

        $this->assertFalse($result);
    }

    public function testGetDeltagare(): void
    {
        $expectedData = [
            [
                'medlem_id' => 1,
                'fornamn' => 'John',
                'efternamn' => 'Doe',
                'roll_id' => 1,
                'roll_namn' => 'Skeppare'
            ]
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute');

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        $result = $this->repository->getDeltagare(1);

        $this->assertEquals($expectedData, $result);
    }

    public function testIsMemberOnSegling(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute');

        $this->mockStmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(1);

        $result = $this->repository->isMemberOnSegling(1, 2);

        $this->assertTrue($result);
    }

    public function testAddMemberToSeglingWithRole(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('INSERT INTO Segling_Medlem_Roll (segling_id, medlem_id, roll_id) VALUES (:segling_id, :medlem_id, :roll_id)')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStmt->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->repository->addMemberToSegling(1, 2, 3);

        $this->assertTrue($result);
    }

    public function testAddMemberToSeglingWithoutRole(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('INSERT INTO Segling_Medlem_Roll (segling_id, medlem_id) VALUES (:segling_id, :medlem_id)')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStmt->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->repository->addMemberToSegling(1, 2, null);

        $this->assertTrue($result);
    }

    public function testRemoveMemberFromSegling(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('DELETE FROM Segling_Medlem_Roll WHERE segling_id = :segling_id AND medlem_id = :medlem_id')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $result = $this->repository->removeMemberFromSegling(1, 2);

        $this->assertTrue($result);
    }

    public function testGetByIdWithDeltagare(): void
    {
        $seglingData = [
            'id' => 1,
            'startdatum' => '2024-01-01',
            'slutdatum' => '2024-01-02',
            'skeppslag' => 'Test Crew',
            'kommentar' => 'Test comment',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00'
        ];

        $deltagareData = [
            ['medlem_id' => 1, 'fornamn' => 'John', 'efternamn' => 'Doe', 'roll_namn' => 'Skeppare']
        ];

        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->exactly(2))
            ->method('execute');

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($seglingData);

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($deltagareData);

        $result = $this->repository->getByIdWithDeltagare(1);

        $this->assertInstanceOf(Segling::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals($deltagareData, $result->deltagare);
    }

    public function testLegacyMethods(): void
    {
        // Test that legacy methods still work for backward compatibility
        $data = ['startdat' => '2024-01-01', 'slutdat' => '2024-01-02', 'skeppslag' => 'Test'];

        $this->mockPdo->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->mockStmt->expects($this->exactly(3))
            ->method('rowCount')
            ->willReturnOnConsecutiveCalls(1, 1, 1);

        $this->mockPdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('123');

        // Test legacy create method
        $createResult = $this->repository->createSegling($data);
        $this->assertEquals(123, $createResult);

        // Test legacy update method
        $updateResult = $this->repository->updateSegling(1, $data);
        $this->assertTrue($updateResult);

        // Test legacy delete method
        $deleteResult = $this->repository->deleteSegling(1);
        $this->assertTrue($deleteResult);
    }
}