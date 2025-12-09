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

        $result = $this->repository->findMembersByRollName('Skeppare');

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

        $result = $this->repository->findMembersByRollId(1);

        $this->assertEquals($expectedMembers, $result);
    }

    public function testFindMemberByEmail(): void
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

        $result = $this->repository->findMemberByEmail('john@example.com');

        $this->assertEquals($expectedData, $result);
    }

    public function testFindMemberByEmailNotFound(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $result = $this->repository->findMemberByEmail('notfound@example.com');

        $this->assertFalse($result);
    }

    public function testFindEmailsForActiveMembers(): void
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

        $result = $this->repository->findEmailsForActiveMembers();

        $this->assertCount(2, $result);
        $this->assertEquals('john@example.com', $result[0]['email']);
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

    public function testGetAll(): void
    {
        $memberIds = [['id' => 1], ['id' => 2]];
        $memberData = [
            'id' => 1,
            'fornamn' => 'John',
            'efternamn' => 'Doe',
            'email' => 'john@example.com',
            'fodelsedatum' => '1990-01-01',
            'gatuadress' => '123 Main St',
            'postnummer' => '12345',
            'postort' => 'Stockholm',
            'mobil' => '0701234567',
            'telefon' => '0812345678',
            'kommentar' => 'Test',
            'godkant_gdpr' => 1,
            'pref_kommunikation' => 1,
            'foretag' => 0,
            'standig_medlem' => 1,
            'skickat_valkomstbrev' => 0,
            'isAdmin' => 0,
            'password' => null,
            'created_at' => '2024-01-01',
            'updated_at' => '2024-01-01'
        ];

        $mockStmt1 = $this->createMock(PDOStatement::class);
        $mockStmt2 = $this->createMock(PDOStatement::class);
        $mockStmt3 = $this->createMock(PDOStatement::class);

        $this->mockPdo->expects($this->exactly(5))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($mockStmt1, $mockStmt2, $mockStmt3, $mockStmt2, $mockStmt3);

        $mockStmt1->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($memberIds);

        $mockStmt2->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($memberData);

        $mockStmt3->expects($this->exactly(2))
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([]);

        $result = $this->repository->getAll();

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(Medlem::class, $result);
    }

    public function testSaveNewMedlem(): void
    {
        $medlem = new Medlem();
        $medlem->fornamn = 'John';
        $medlem->efternamn = 'Doe';
        $medlem->email = 'john@example.com';
        $medlem->fodelsedatum = '1990-01-01';
        $medlem->adress = '123 Main St';
        $medlem->postnummer = '12345';
        $medlem->postort = 'Stockholm';
        $medlem->mobil = '0701234567';
        $medlem->telefon = '0812345678';
        $medlem->kommentar = 'Test';
        $medlem->godkant_gdpr = true;
        $medlem->pref_kommunikation = true;
        $medlem->isAdmin = false;
        $medlem->foretag = false;
        $medlem->standig_medlem = true;
        $medlem->skickat_valkomstbrev = false;
        $medlem->roller = [];

        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockPdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('123');

        $this->mockPdo->expects($this->once())
            ->method('beginTransaction');

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_COLUMN)
            ->willReturn([]);

        $this->mockPdo->expects($this->once())
            ->method('commit');

        $result = $this->repository->save($medlem);

        $this->assertTrue($result);
        $this->assertEquals(123, $medlem->id);
    }

    public function testSaveExistingMedlem(): void
    {
        $medlem = new Medlem();
        $medlem->id = 1;
        $medlem->fornamn = 'John';
        $medlem->efternamn = 'Doe';
        $medlem->email = 'john@example.com';
        $medlem->fodelsedatum = '1990-01-01';
        $medlem->adress = '123 Main St';
        $medlem->postnummer = '12345';
        $medlem->postort = 'Stockholm';
        $medlem->mobil = '0701234567';
        $medlem->telefon = '0812345678';
        $medlem->kommentar = 'Test';
        $medlem->godkant_gdpr = true;
        $medlem->pref_kommunikation = true;
        $medlem->isAdmin = false;
        $medlem->foretag = false;
        $medlem->standig_medlem = true;
        $medlem->skickat_valkomstbrev = false;
        $medlem->roller = [];

        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockPdo->expects($this->once())
            ->method('beginTransaction');

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_COLUMN)
            ->willReturn([]);

        $this->mockPdo->expects($this->once())
            ->method('commit');

        $result = $this->repository->save($medlem);

        $this->assertTrue($result);
    }

    public function testSaveFailure(): void
    {
        $medlem = new Medlem();
        $medlem->fornamn = 'John';
        $medlem->efternamn = 'Doe';
        $medlem->email = 'john@example.com';
        $medlem->fodelsedatum = '1990-01-01';
        $medlem->adress = '123 Main St';
        $medlem->postnummer = '12345';
        $medlem->postort = 'Stockholm';
        $medlem->mobil = '0701234567';
        $medlem->telefon = '0812345678';
        $medlem->kommentar = 'Test';
        $medlem->godkant_gdpr = true;
        $medlem->pref_kommunikation = true;
        $medlem->isAdmin = false;
        $medlem->foretag = false;
        $medlem->standig_medlem = true;
        $medlem->skickat_valkomstbrev = false;

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willThrowException(new Exception('Database error'));

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to save medlem'));

        $result = $this->repository->save($medlem);

        $this->assertFalse($result);
    }

    public function testGetByIdReturnsNull(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $result = $this->repository->getById(999);

        $this->assertNull($result);
    }

    public function testDelete(): void
    {
        $medlem = new Medlem();
        $medlem->id = 1;

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

        $result = $this->repository->delete($medlem);

        $this->assertTrue($result);
    }
}
