<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\MedlemRepository;
use App\Models\Medlem;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;
use Exception;

class MedlemRepositoryTest extends TestCase
{
    private MedlemRepository $repository;
    private $mockPdo;
    private $mockLogger;
    private $mockStmt;

    protected function setUp(): void
    {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->mockStmt = $this->createMock(PDOStatement::class);

        $this->repository = new MedlemRepository($this->mockPdo, $this->mockLogger);
    }

    public function testGetMembersByRollName(): void
    {
        $expectedMembers = [
            ['id' => 1, 'fornamn' => 'Test', 'efternamn' => 'Person', 'roll_namn' => 'Skeppare'],
            ['id' => 2, 'fornamn' => 'Another', 'efternamn' => 'Member', 'roll_namn' => 'Skeppare']
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute');

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedMembers);

        $result = $this->repository->getMembersByRollName('Skeppare');

        $this->assertCount(2, $result);
        $this->assertEquals($expectedMembers, $result);
    }

    public function testGetMembersByRollId(): void
    {
        $expectedMembers = [
            ['id' => 1, 'fornamn' => 'Test', 'efternamn' => 'Person', 'roll_id' => 1, 'roll_namn' => 'Skeppare']
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute');

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedMembers);

        $result = $this->repository->getMembersByRollId(1);

        $this->assertEquals($expectedMembers, $result);
    }

    public function testGetMemberByEmail(): void
    {
        $expectedData = ['id' => 1, 'fornamn' => 'John', 'efternamn' => 'Doe', 'email' => 'john@example.com'];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM medlem WHERE email = :email')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        $result = $this->repository->getMemberByEmail('john@example.com');

        $this->assertEquals($expectedData, $result);
    }

    public function testGetMemberByEmailNotFound(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $result = $this->repository->getMemberByEmail('notfound@example.com');

        $this->assertFalse($result);
    }

    public function testGetEmailForActiveMembers(): void
    {
        $expectedData = [
            ['email' => 'john@example.com'],
            ['email' => 'jane@example.com'],
            ['email' => '']
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT email FROM medlem WHERE pref_kommunikation = 1')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        $result = $this->repository->getEmailForActiveMembers();

        $this->assertCount(2, $result);
        $this->assertEquals('john@example.com', $result[0]['email']);
    }

    public function testFindById(): void
    {
        $expectedData = ['id' => 1, 'fornamn' => 'John', 'efternamn' => 'Doe'];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM Medlem WHERE id = :id LIMIT 1')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        $result = $this->repository->findById(1);

        $this->assertEquals($expectedData, $result);
    }

    public function testFindByIdNotFound(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function testInsert(): void
    {
        $data = [
            'fodelsedatum' => '1990-01-01',
            'fornamn' => 'John',
            'efternamn' => 'Doe',
            'email' => 'john@example.com',
            'adress' => '123 Main St',
            'postnummer' => '12345',
            'postort' => 'Stockholm',
            'mobil' => '0701234567',
            'telefon' => '0812345678',
            'kommentar' => 'Test member',
            'godkant_gdpr' => true,
            'pref_kommunikation' => true,
            'isAdmin' => false,
            'foretag' => false,
            'standig_medlem' => true,
            'skickat_valkomstbrev' => false
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockPdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('123');

        $result = $this->repository->insert($data);

        $this->assertEquals(123, $result);
    }

    public function testUpdate(): void
    {
        $data = ['fornamn' => 'John', 'efternamn' => 'Doe', 'email' => 'john@example.com'];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $result = $this->repository->update(1, $data);

        $this->assertTrue($result);
    }

    public function testDeleteById(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('beginTransaction');

        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->mockPdo->expects($this->once())
            ->method('commit');

        $result = $this->repository->deleteById(1);

        $this->assertTrue($result);
    }

    public function testDeleteByIdFailure(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('beginTransaction');

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->willThrowException(new Exception('Database error'));

        $this->mockPdo->expects($this->once())
            ->method('rollBack');

        $this->mockLogger->expects($this->once())
            ->method('error');

        $result = $this->repository->deleteById(1);

        $this->assertFalse($result);
    }

    public function testGetRolesByMemberId(): void
    {
        $expectedData = [
            ['roll_id' => 1, 'roll_namn' => 'Skeppare'],
            ['roll_id' => 2, 'roll_namn' => 'Båtsman']
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        $result = $this->repository->getRolesByMemberId(1);

        $this->assertEquals($expectedData, $result);
    }

    public function testGetSeglingarByMemberId(): void
    {
        $expectedData = [
            ['medlem_id' => 1, 'segling_id' => 1, 'roll_namn' => 'Skeppare', 'skeppslag' => 'Test Crew', 'startdatum' => '2024-01-01']
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        $result = $this->repository->getSeglingarByMemberId(1);

        $this->assertEquals($expectedData, $result);
    }

    public function testCreateNew(): void
    {
        $result = $this->repository->createNew();

        $this->assertInstanceOf(Medlem::class, $result);
    }

    public function testSaveRolesForMemberAddRoles(): void
    {
        $roles = [['roll_id' => 1], ['roll_id' => 2]];

        $this->mockPdo->expects($this->once())
            ->method('beginTransaction');

        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_COLUMN)
            ->willReturn([]);

        $this->mockStmt->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->mockPdo->expects($this->once())
            ->method('commit');

        $this->repository->saveRolesForMember(1, $roles);

        $this->assertTrue(true);
    }
}
