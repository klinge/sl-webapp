<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\RollRepository;
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
            ['id' => 1, 'roll_namn' => 'Skeppare', 'kommentar' => 'Captain role'],
            ['id' => 2, 'roll_namn' => 'Båtsman', 'kommentar' => 'Crew role']
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

        $this->assertEquals($expectedData, $result);
    }
}
