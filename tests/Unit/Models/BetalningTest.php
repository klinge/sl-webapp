<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Betalning;
use PDO;
use PDOStatement;

class BetalningTest extends TestCase
{
    private $mockPdo;
    private $mockStatement;
    private $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStatement = $this->createMock(PDOStatement::class);
        $this->mockLogger = $this->createMock(\Psr\Log\LoggerInterface::class);
    }

    public function testConstructorWithPaymentData()
    {
        $paymentData = [
            'id' => 1,
            'belopp' => 100.50,
            'medlem_id' => 2,
            'datum' => '2023-05-01',
            'avser_ar' => 2023,
            'kommentar' => 'Test payment',
            'created_at' => '2023-05-01 10:00:00',
            'updated_at' => '2023-05-01 10:00:00'
        ];

        $betalning = new Betalning($this->mockPdo, $this->mockLogger, $paymentData);

        $this->assertEquals(1, $betalning->id);
        $this->assertEquals(100.50, $betalning->belopp);
        $this->assertEquals(2, $betalning->medlem_id);
        $this->assertEquals('2023-05-01', $betalning->datum);
        $this->assertEquals(2023, $betalning->avser_ar);
        $this->assertEquals('Test payment', $betalning->kommentar);
        $this->assertEquals('2023-05-01 10:00:00', $betalning->created_at);
        $this->assertEquals('2023-05-01 10:00:00', $betalning->updated_at);
    }

    public function testGet()
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement->expects($this->once())
            ->method('execute');

        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'belopp' => 200.75,
                'medlem_id' => 3,
                'datum' => '2023-05-02',
                'avser_ar' => 2023,
                'kommentar' => 'Another test payment',
                'created_at' => '2023-05-02 11:00:00',
                'updated_at' => '2023-05-02 11:00:00'
            ]);

        $betalning = new Betalning($this->mockPdo, $this->mockLogger);
        $betalning->get(2);

        $this->assertEquals(2, $betalning->id);
        $this->assertEquals(200.75, $betalning->belopp);
        $this->assertEquals(3, $betalning->medlem_id);
        $this->assertEquals('2023-05-02', $betalning->datum);
        $this->assertEquals(2023, $betalning->avser_ar);
        $this->assertEquals('Another test payment', $betalning->kommentar);
        $this->assertEquals('2023-05-02 11:00:00', $betalning->created_at);
        $this->assertEquals('2023-05-02 11:00:00', $betalning->updated_at);
    }

    public function testCreate()
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockPdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('3');

        $betalning = new Betalning($this->mockPdo, $this->mockLogger);
        $betalning->medlem_id = 4;
        $betalning->belopp = 300.25;
        $betalning->datum = '2023-05-03';
        $betalning->avser_ar = 2023;
        $betalning->kommentar = 'New payment';

        $result = $betalning->create();

        $this->assertTrue($result['success']);
        $this->assertEquals('Betalning created successfully', $result['message']);
        $this->assertEquals(3, $result['id']);
    }

    public function testCreateWithError()
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $this->mockStatement->expects($this->once())
            ->method('errorInfo')
            ->willReturn([null, null, 'Test error message']);

        $betalning = new Betalning($this->mockPdo, $this->mockLogger);
        $betalning->medlem_id = 5;
        $betalning->belopp = 400.00;
        $betalning->datum = '2023-05-04';
        $betalning->avser_ar = 2023;
        $betalning->kommentar = 'Error payment';

        $result = $betalning->create();

        $this->assertFalse($result['success']);
        $this->assertEquals('Error creating Betalning: Test error message', $result['message']);
    }

    public function testDelete()
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $betalning = new Betalning($this->mockPdo, $this->mockLogger);
        $betalning->id = 6;

        $result = $betalning->delete();

        $this->assertTrue($result['success']);
        $this->assertEquals('Betalning deleted successfully', $result['message']);
    }

    public function testDeleteWithError()
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $this->mockStatement->expects($this->once())
            ->method('errorInfo')
            ->willReturn([null, null, 'Test delete error message']);

        $betalning = new Betalning($this->mockPdo, $this->mockLogger);
        $betalning->id = 7;

        $result = $betalning->delete();

        $this->assertFalse($result['success']);
        $this->assertEquals('Error deleting Betalning: Test delete error message', $result['message']);
    }
}
