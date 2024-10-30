<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Medlem;
use Tests\Unit\Models\MedlemRepositoryFake;
use App\Application;
use PDO;
use Monolog\Logger;

class MedlemRepositoryTest extends TestCase
{
    private $conn;
    private $logger;
    private $app;
    private $medlemRepository;

    protected function setUp(): void
    {
        $this->conn = $this->createMock(PDO::class);
        $this->app  = $this->createMock(Application::class);
        $this->logger = $this->createMock(Logger::class);
        $this->medlemRepository = new MedlemRepositoryFake($this->conn, $this->app);
    }

    public function testGetAllSuccess(): void
    {
        // Arrange
        $memberIds = [
            ['id' => 1],
            ['id' => 2]
        ];

        $pdoStatement = $this->createMock(\PDOStatement::class);
        $pdoStatement->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($memberIds);

        $this->conn->expects($this->once())
            ->method('prepare')
            ->with('SELECT id FROM Medlem ORDER BY efternamn ASC')
            ->willReturn($pdoStatement);

        $pdoStatement->expects($this->once())
            ->method('execute');

        // Act
        $result = $this->medlemRepository->getAll();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        foreach ($result as $medlem) {
            $this->assertInstanceOf(Medlem::class, $medlem);
        }
    }

    public function testGetMembersByRollNamnSuccess(): void
    {
        // Arrange
        $expectedMembers = [
            [
                'id' => 1,
                'fornamn' => 'Test',
                'efternamn' => 'Person',
                'roll_namn' => 'Skeppare'
            ],
            [
                'id' => 2,
                'fornamn' => 'Another',
                'efternamn' => 'Member',
                'roll_namn' => 'Skeppare'
            ]
        ];

        $pdoStatement = $this->createMock(\PDOStatement::class);
        $pdoStatement->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedMembers);

        $this->conn->expects($this->once())
            ->method('prepare')
            ->with("SELECT m.id,m.fornamn, m.efternamn, r.roll_namn
            FROM  Medlem m
            INNER JOIN Medlem_Roll mr ON mr.medlem_id = m.id
            INNER JOIN Roll r ON r.id = mr.roll_id
            WHERE r.roll_namn = :rollnamn
            ORDER BY m.efternamn ASC;")
            ->willReturn($pdoStatement);

        $pdoStatement->expects($this->once())
            ->method('execute');

        // Act
        $result = $this->medlemRepository->getMembersByRollName('Skeppare');

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals($expectedMembers, $result);
    }

    public function testGetMembersByRollIdSuccess(): void
    {
        // Arrange
        $expectedMembers = [
            [
                'id' => 1,
                'fornamn' => 'Test',
                'efternamn' => 'Person',
                'roll_id' => 1,
                'roll_namn' => 'Skeppare'
            ],
            [
                'id' => 2,
                'fornamn' => 'Another',
                'efternamn' => 'Member',
                'roll_id' => 1,
                'roll_namn' => 'Skeppare'
            ]
        ];

        $pdoStatement = $this->createMock(\PDOStatement::class);
        $pdoStatement->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedMembers);

        $this->conn->expects($this->once())
            ->method('prepare')
            ->with("SELECT m.id,m.fornamn, m.efternamn, r.id AS roll_id, r.roll_namn
            FROM  Medlem m
            INNER JOIN Medlem_Roll mr ON mr.medlem_id = m.id
            INNER JOIN Roll r ON r.id = mr.roll_id
            WHERE r.id = :id
            ORDER BY m.fornamn ASC;")
            ->willReturn($pdoStatement);

        $pdoStatement->expects($this->once())
            ->method('execute');

        // Act
        $result = $this->medlemRepository->getMembersByRollId(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals($expectedMembers, $result);
    }
}
