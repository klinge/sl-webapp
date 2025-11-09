<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Roll;

class RollTest extends TestCase
{
    public function testRollObjectCreation(): void
    {
        $roll = new Roll();

        $this->assertInstanceOf(Roll::class, $roll);
    }

    public function testRollHasExpectedProperties(): void
    {
        $roll = new Roll();

        $this->assertTrue(property_exists($roll, 'id'));
        $this->assertTrue(property_exists($roll, 'roll_namn'));
        $this->assertTrue(property_exists($roll, 'kommentar'));
        $this->assertTrue(property_exists($roll, 'created_at'));
        $this->assertTrue(property_exists($roll, 'updated_at'));
    }

    public function testGetRollNamn(): void
    {
        $roll = new Roll();
        $roll->roll_namn = 'Skeppare';

        $result = $roll->getRollNamn();

        $this->assertEquals('Skeppare', $result);
    }

    public function testRollPropertiesCanBeSet(): void
    {
        $roll = new Roll();
        $roll->id = 1;
        $roll->roll_namn = 'Båtsman';
        $roll->kommentar = 'Crew member role';
        $roll->created_at = '2024-01-01 00:00:00';
        $roll->updated_at = '2024-01-01 00:00:00';

        $this->assertEquals(1, $roll->id);
        $this->assertEquals('Båtsman', $roll->roll_namn);
        $this->assertEquals('Crew member role', $roll->kommentar);
        $this->assertEquals('2024-01-01 00:00:00', $roll->created_at);
        $this->assertEquals('2024-01-01 00:00:00', $roll->updated_at);
    }
}
