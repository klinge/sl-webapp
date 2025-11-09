<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\RollRepository;
use App\Models\Roll;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;

class RollRepositoryTest extends TestCase
{
    private RollRepository $repository;
    private $mockPdo;
    private $mockLogger;
    private $mockStmt;

    protected function setUp(): void
    {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->mockStmt = $this->createMock(PDOStatement::class);

        $this->repository = new RollRepository($this->mockPdo, $this->mockLogger);
    }

    public function testGetAll(): void
    {
        $expectedData = [
            [
                'id' => 1,
                'roll_namn' => 'Skeppare',
                'kommentar' => 'Captain role',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00'
            ],
            [
                'id' => 2,
                'roll_namn' => 'Båtsman',
                'kommentar' => 'Crew role',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00'
            ]
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM Roll')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute');

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        $result = $this->repository->getAll();

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Roll::class, $result[0]);
        $this->assertInstanceOf(Roll::class, $result[1]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('Skeppare', $result[0]->roll_namn);
        $this->assertEquals('Captain role', $result[0]->kommentar);
        $this->assertEquals('2024-01-01 00:00:00', $result[0]->created_at);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals('Båtsman', $result[1]->roll_namn);
        $this->assertEquals('Crew role', $result[1]->kommentar);
        $this->assertEquals('2024-01-01 00:00:00', $result[1]->created_at);
    }
}
