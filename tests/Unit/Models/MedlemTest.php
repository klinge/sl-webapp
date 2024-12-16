<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Medlem;
use PDO;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

class MedlemTest extends TestCase
{
    private $db;
    private $logger;
    private $medlem;

    protected function setUp(): void
    {
        $this->db = $this->createMock(PDO::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->medlem = $this->getMockBuilder(Medlem::class)
            ->setConstructorArgs([$this->db, $this->logger])
            ->onlyMethods(['persistToDatabase'])
            ->getMock();
        $this->medlem->fornamn = 'Test';
        $this->medlem->efternamn = 'Person';
        $this->medlem->email = 'test@example.com';
        $this->medlem->mobil = '1234567890';
    }

    public function testSaveCallsPersistToDatabaseWithUpdate()
    {
        $this->medlem->expects($this->once())
            ->method('persistToDatabase')
            ->with('UPDATE')
            ->willReturn(true);

        $this->medlem->id = 1;
        $result = $this->medlem->save();

        $this->assertEquals(1, $result);
    }

    public function testCreateCallsPersistToDatabaseWithInsert()
    {
        $this->medlem->expects($this->once())
            ->method('persistToDatabase')
            ->with('INSERT')
            ->willReturn(true);

        $this->medlem->id = 1;
        $result = $this->medlem->create();

        $this->assertGreaterThan(0, $result);
    }

    public function testGetNamn()
    {
        $medlem = new Medlem($this->db, $this->logger);
        $medlem->fornamn = "John";
        $medlem->efternamn = "Doe";

        $this->assertEquals("John Doe", $medlem->getNamn());
    }

    public function testHasRole()
    {
        $medlem = new Medlem($this->db, $this->logger);
        $medlem->roller = [
            ['roll_id' => '1'],
            ['roll_id' => '2']
        ];

        $this->assertTrue($medlem->hasRole('1'));
        $this->assertTrue($medlem->hasRole('2'));
        $this->assertFalse($medlem->hasRole('3'));
    }

    public function testUpdateMedlemRoles()
    {
        $medlem = new Medlem($this->db, $this->logger);
        $medlem->roller = [
            ['roll_id' => '1'],
            ['roll_id' => '2']
        ];

        $medlem->updateMedlemRoles(['2', '3']);

        $this->assertCount(2, $medlem->roller);
        $this->assertTrue($medlem->hasRole('2'));
        $this->assertTrue($medlem->hasRole('3'));
        $this->assertFalse($medlem->hasRole('1'));
    }

    public function testSaveUserProvidedPassword()
    {
        $medlem = new Medlem($this->db, $this->logger);
        $medlem->id = 1;
        $medlem->password = 'testpassword';

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute');

        $this->db->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $medlem->saveUserProvidedPassword();
    }

    public function testDelete()
    {
        $medlem = new Medlem($this->db, $this->logger);
        $medlem->id = 1;
        $medlem->fornamn = "John";
        $medlem->efternamn = "Doe";

        $stmt_del_member = $this->createMock(\PDOStatement::class);
        $stmt_del_roles = $this->createMock(\PDOStatement::class);

        $stmt_del_member->expects($this->once())
            ->method('execute');

        $stmt_del_roles->expects($this->once())
            ->method('execute');

        $this->db->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmt_del_member, $stmt_del_roles);


        $this->logger->expects($this->once())
            ->method('info');

        $medlem->delete();

        $this->assertEmpty($medlem->roller);
    }
}
