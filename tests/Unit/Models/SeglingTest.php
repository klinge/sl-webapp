<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Segling;

class SeglingTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $segling = new Segling(
            id: 1,
            start_dat: '2023-06-01',
            slut_dat: '2023-06-02',
            skeppslag: 'Test Crew',
            kommentar: 'Test comment',
            deltagare: [
                ['medlem_id' => 1, 'fornamn' => 'John', 'efternamn' => 'Doe', 'roll_namn' => 'Skeppare']
            ],
            created_at: '2023-01-01 00:00:00',
            updated_at: '2023-01-01 00:00:00'
        );

        $this->assertEquals(1, $segling->id);
        $this->assertEquals('2023-06-01', $segling->start_dat);
        $this->assertEquals('2023-06-02', $segling->slut_dat);
        $this->assertEquals('Test Crew', $segling->skeppslag);
        $this->assertEquals('Test comment', $segling->kommentar);
        $this->assertCount(1, $segling->deltagare);
        $this->assertEquals('2023-01-01 00:00:00', $segling->created_at);
        $this->assertEquals('2023-01-01 00:00:00', $segling->updated_at);
    }

    public function testConstructorWithDefaults(): void
    {
        $segling = new Segling();

        $this->assertEquals(0, $segling->id);
        $this->assertEquals('', $segling->start_dat);
        $this->assertEquals('', $segling->slut_dat);
        $this->assertEquals('', $segling->skeppslag);
        $this->assertNull($segling->kommentar);
        $this->assertEmpty($segling->deltagare);
        $this->assertEquals('', $segling->created_at);
        $this->assertEquals('', $segling->updated_at);
    }

    public function testGetDeltagareByRoleName(): void
    {
        $deltagare = [
            ['medlem_id' => 1, 'fornamn' => 'John', 'efternamn' => 'Doe', 'roll_namn' => 'Skeppare'],
            ['medlem_id' => 2, 'fornamn' => 'Jane', 'efternamn' => 'Smith', 'roll_namn' => 'Båtsman'],
            ['medlem_id' => 3, 'fornamn' => 'Bob', 'efternamn' => 'Johnson', 'roll_namn' => 'Skeppare']
        ];

        $segling = new Segling(deltagare: $deltagare);
        $skeppare = $segling->getDeltagareByRoleName('Skeppare');

        $this->assertCount(2, $skeppare);
        $this->assertEquals(1, $skeppare[0]['id']);
        $this->assertEquals('John', $skeppare[0]['fornamn']);
        $this->assertEquals('Doe', $skeppare[0]['efternamn']);
        $this->assertEquals(3, $skeppare[1]['id']);
        $this->assertEquals('Bob', $skeppare[1]['fornamn']);
        $this->assertEquals('Johnson', $skeppare[1]['efternamn']);
    }

    public function testGetDeltagareByRoleNameWithNoMatches(): void
    {
        $deltagare = [
            ['medlem_id' => 1, 'fornamn' => 'John', 'efternamn' => 'Doe', 'roll_namn' => 'Båtsman']
        ];

        $segling = new Segling(deltagare: $deltagare);
        $kockar = $segling->getDeltagareByRoleName('Kock');

        $this->assertEmpty($kockar);
    }

    public function testGetDeltagareByRoleNameWithEmptyDeltagare(): void
    {
        $segling = new Segling();
        $result = $segling->getDeltagareByRoleName('Skeppare');

        $this->assertEmpty($result);
    }
}