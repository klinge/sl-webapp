<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Roll;
use PDO;
use Psr\Log\LoggerInterface;

class RollTest extends TestCase
{
    public function testRollModelExists(): void
    {
        $mockPdo = $this->createMock(PDO::class);
        $mockLogger = $this->createMock(LoggerInterface::class);

        $roll = new Roll($mockPdo, $mockLogger);

        $this->assertInstanceOf(Roll::class, $roll);
    }

    public function testRollHasExpectedProperties(): void
    {
        $mockPdo = $this->createMock(PDO::class);
        $mockLogger = $this->createMock(LoggerInterface::class);

        $roll = new Roll($mockPdo, $mockLogger);

        $this->assertTrue(property_exists($roll, 'id'));
        $this->assertTrue(property_exists($roll, 'roll_namn'));
        $this->assertTrue(property_exists($roll, 'kommentar'));
        $this->assertTrue(property_exists($roll, 'created_at'));
        $this->assertTrue(property_exists($roll, 'updated_at'));
    }
}
