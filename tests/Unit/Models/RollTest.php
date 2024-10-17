<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Roll;
use PDO;
use PDOStatement;

class RollTest extends TestCase
{
    private $mockPDO;
    private $roll;

    protected function setUp(): void
    {
        $this->mockPDO = $this->createMock(PDO::class);
        $this->roll = new Roll($this->mockPDO);
    }

    public function testGetAllReturnsArray()
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('execute')->willReturn(true);
        $mockStatement->method('fetchAll')->willReturn([
            ['id' => 1, 'roll_namn' => 'Admin', 'kommentar' => 'Administrator', 'created_at' => '2023-01-01', 'updated_at' => '2023-01-01'],
            ['id' => 2, 'roll_namn' => 'User', 'kommentar' => 'Regular User', 'created_at' => '2023-01-02', 'updated_at' => '2023-01-02']
        ]);

        $this->mockPDO->method('prepare')->willReturn($mockStatement);

        $result = $this->roll->getAll();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Admin', $result[0]['roll_namn']);
        $this->assertEquals('User', $result[1]['roll_namn']);
    }

    public function testGetAllWithEmptyResult()
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('execute')->willReturn(true);
        $mockStatement->method('fetchAll')->willReturn([]);

        $this->mockPDO->method('prepare')->willReturn($mockStatement);

        $result = $this->roll->getAll();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
