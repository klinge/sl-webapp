<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Medlem;

class MedlemTest extends TestCase
{
    private Medlem $medlem;

    protected function setUp(): void
    {
        $this->medlem = new Medlem();
        $this->medlem->fornamn = 'Test';
        $this->medlem->efternamn = 'Person';
        $this->medlem->email = 'test@example.com';
        $this->medlem->mobil = '1234567890';
    }

    public function testGetNamn(): void
    {
        $medlem = new Medlem();
        $medlem->fornamn = "John";
        $medlem->efternamn = "Doe";

        $this->assertEquals("John Doe", $medlem->getNamn());
    }

    public function testHasRole(): void
    {
        $medlem = new Medlem();
        $medlem->roller = [
            ['roll_id' => '1'],
            ['roll_id' => '2']
        ];

        $this->assertTrue($medlem->hasRole('1'));
        $this->assertTrue($medlem->hasRole('2'));
        $this->assertFalse($medlem->hasRole('3'));
    }

    public function testUpdateMedlemRoles(): void
    {
        $medlem = new Medlem();
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

    public function testDefaultValues(): void
    {
        $medlem = new Medlem();

        $this->assertFalse($medlem->godkant_gdpr);
        $this->assertTrue($medlem->pref_kommunikation);
        $this->assertFalse($medlem->foretag);
        $this->assertFalse($medlem->standig_medlem);
        $this->assertFalse($medlem->skickat_valkomstbrev);
        $this->assertFalse($medlem->isAdmin);
        $this->assertNull($medlem->password);
        $this->assertEmpty($medlem->roller);
    }
}
