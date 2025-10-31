<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\BetalningRepository;
use App\Models\Betalning;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;

class BetalningRepositoryTest extends TestCase
{
    private BetalningRepository $repository;
    private $mockPdo;
    private $mockLogger;
    private $mockStmt;

    protected function setUp(): void
    {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->mockStmt = $this->createMock(PDOStatement::class);
        
        $this->repository = new BetalningRepository($this->mockPdo, $this->mockLogger);
    }

    public function testGetAll(): void
    {
        $expectedData = [
            [
                'id' => 1,
                'medlem_id' => 1,
                'belopp' => '500.00',
                'datum' => '2024-01-01',
                'avser_ar' => 2024,
                'kommentar' => 'Test payment',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00'
            ]
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * from Betalning ORDER BY datum DESC')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute');

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        $result = $this->repository->getAll();

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Betalning::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals(500.00, $result[0]->belopp);
    }

    public function testGetAllWithName(): void
    {
        $expectedData = [
            [
                'id' => 1,
                'medlem_id' => 1,
                'belopp' => '500.00',
                'datum' => '2024-01-01',
                'avser_ar' => 2024,
                'kommentar' => 'Test payment',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00',
                'fornamn' => 'John',
                'efternamn' => 'Doe'
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

        $result = $this->repository->getAllWithName();

        $this->assertEquals($expectedData, $result);
    }

    public function testGetBetalningForMedlem(): void
    {
        $expectedData = [
            [
                'id' => 1,
                'medlem_id' => 1,
                'belopp' => '500.00',
                'datum' => '2024-01-01',
                'avser_ar' => 2024,
                'kommentar' => 'Test payment',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00'
            ]
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * from Betalning WHERE medlem_id = ? ORDER BY datum DESC')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('bindParam')
            ->with(1, 1, PDO::PARAM_INT);

        $this->mockStmt->expects($this->once())
            ->method('execute');

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        $result = $this->repository->getBetalningForMedlem(1);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Betalning::class, $result[0]);
    }

    public function testMemberHasPayedTrue(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute');

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([['id' => 1]]);

        $result = $this->repository->memberHasPayed(1, 2024);

        $this->assertTrue($result);
    }

    public function testMemberHasPayedFalse(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute');

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([]);

        $result = $this->repository->memberHasPayed(1, 2024);

        $this->assertFalse($result);
    }

    public function testCreate(): void
    {
        $betalning = new Betalning();
        $betalning->medlem_id = 1;
        $betalning->belopp = 500.00;
        $betalning->datum = '2024-01-01';
        $betalning->avser_ar = 2024;
        $betalning->kommentar = 'Test payment';

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('INSERT INTO Betalning (medlem_id, belopp, datum, avser_ar, kommentar) VALUES (?, ?, ?, ?, ?)')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute');

        $this->mockPdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('123');

        $result = $this->repository->create($betalning);

        $this->assertEquals(123, $result);
    }

    public function testDeleteById(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('DELETE FROM Betalning WHERE id = ?')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('bindParam')
            ->with(1, 1, PDO::PARAM_INT);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $result = $this->repository->deleteById(1);

        $this->assertTrue($result);
    }

    public function testGetById(): void
    {
        $expectedData = [
            'id' => 1,
            'medlem_id' => 1,
            'belopp' => '500.00',
            'datum' => '2024-01-01',
            'avser_ar' => 2024,
            'kommentar' => 'Test payment',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00'
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM Betalning WHERE id = ? LIMIT 1')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute');

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        $result = $this->repository->getById(1);

        $this->assertInstanceOf(Betalning::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals(500.00, $result->belopp);
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

    public function testCreateNew(): void
    {
        $result = $this->repository->createNew();

        $this->assertInstanceOf(Betalning::class, $result);
        $this->assertEquals(0, $result->id);
    }
}