<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Segling;
use PDO;
use PDOStatement;

class SeglingTest extends TestCase
{
    private $mockPDO;
    private $mockLogger;
    private $mockStatement;

    protected function setUp(): void
    {
        $this->mockPDO = $this->createMock(PDO::class);
        $this->mockLogger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->mockStatement = $this->createMock(PDOStatement::class);
    }

    public function testConstructorWithValidId()
    {
        $this->mockStatement->method('fetch')->willReturn([
            'startdatum' => '2023-01-01',
            'slutdatum' => '2023-01-07',
            'skeppslag' => 'Test Skeppslag',
            'kommentar' => 'Test Kommentar',
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00'
        ]);

        $this->mockPDO->method('prepare')->willReturn($this->mockStatement);

        $segling = new Segling($this->mockPDO, $this->mockLogger, 1);

        $this->assertEquals(1, $segling->id);
        $this->assertEquals('2023-01-01', $segling->start_dat);
        $this->assertEquals('2023-01-07', $segling->slut_dat);
        $this->assertEquals('Test Skeppslag', $segling->skeppslag);
        $this->assertEquals('Test Kommentar', $segling->kommentar);
    }

    public function testConstructorWithInvalidId()
    {
        $this->mockStatement->method('fetch')->willReturn(false);
        $this->mockPDO->method('prepare')->willReturn($this->mockStatement);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Segling med id: 999 hittades inte");

        $segling = new Segling($this->mockPDO, $this->mockLogger, 999);
        $this->assertNull($segling->id);
    }

    public function testSaveMethod()
    {
        $this->mockStatement->method('rowCount')->willReturn(1);
        $this->mockPDO->method('prepare')->willReturn($this->mockStatement);

        $segling = new Segling($this->mockPDO, $this->mockLogger);
        $segling->id = 1;
        $segling->start_dat = '2023-01-01';
        $segling->slut_dat = '2023-01-07';
        $segling->skeppslag = 'Updated Skeppslag';
        $segling->kommentar = 'Updated Kommentar';

        $result = $segling->save();

        $this->assertTrue($result);
    }

    public function testDeleteMethod()
    {
        $this->mockStatement->method('rowCount')->willReturn(1);
        $this->mockPDO->method('prepare')->willReturn($this->mockStatement);

        $segling = new Segling($this->mockPDO, $this->mockLogger);
        $segling->id = 1;

        $result = $segling->delete();

        $this->assertTrue($result);
    }

    public function testCreateMethod()
    {
        $this->mockStatement->method('rowCount')->willReturn(1);
        $this->mockPDO->method('prepare')->willReturn($this->mockStatement);
        $this->mockPDO->method('lastInsertId')->willReturn('2');

        $segling = new Segling($this->mockPDO, $this->mockLogger);
        $segling->start_dat = '2023-01-01';
        $segling->slut_dat = '2023-01-07';
        $segling->skeppslag = 'New Skeppslag';
        $segling->kommentar = 'New Kommentar';

        $result = $segling->create();

        $this->assertEquals(2, $result);
        $this->assertEquals(2, $segling->id);
    }

    public function testGetDeltagareMethod()
    {
        $mockResult = [
            ['medlem_id' => 1, 'fornamn' => 'John', 'efternamn' => 'Doe', 'roll_id' => 1, 'roll_namn' => 'Captain'],
            ['medlem_id' => 2, 'fornamn' => 'Jane', 'efternamn' => 'Smith', 'roll_id' => 2, 'roll_namn' => 'Crew']
        ];

        $this->mockStatement->method('fetchAll')->willReturn($mockResult);
        $this->mockPDO->method('prepare')->willReturn($this->mockStatement);

        $segling = new Segling($this->mockPDO, $this->mockLogger);
        $segling->id = 1;

        $result = $segling->getDeltagare();

        $this->assertEquals($mockResult, $result);
    }

    public function testGetDeltagareByRoleNameMethod()
    {
        $segling = new Segling($this->mockPDO, $this->mockLogger);
        $segling->deltagare = [
            ['medlem_id' => 1, 'fornamn' => 'John', 'efternamn' => 'Doe', 'roll_id' => 1, 'roll_namn' => 'Captain'],
            ['medlem_id' => 2, 'fornamn' => 'Jane', 'efternamn' => 'Smith', 'roll_id' => 2, 'roll_namn' => 'Crew'],
            ['medlem_id' => 3, 'fornamn' => 'Bob', 'efternamn' => 'Johnson', 'roll_id' => 2, 'roll_namn' => 'Crew']
        ];

        $result = $segling->getDeltagareByRoleName('Crew');

        $expected = [
            ['id' => 2, 'fornamn' => 'Jane', 'efternamn' => 'Smith'],
            ['id' => 3, 'fornamn' => 'Bob', 'efternamn' => 'Johnson']
        ];

        $this->assertEquals($expected, $result);
    }
}
