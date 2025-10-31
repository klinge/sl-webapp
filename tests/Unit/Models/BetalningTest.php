<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Betalning;

class BetalningTest extends TestCase
{
    private Betalning $betalning;

    protected function setUp(): void
    {
        $this->betalning = new Betalning();
    }

    public function testConstructorCreatesEmptyObject(): void
    {
        $betalning = new Betalning();
        
        $this->assertEquals('', $betalning->kommentar);
        $this->assertEquals('', $betalning->created_at);
        $this->assertEquals('', $betalning->updated_at);
    }

    public function testPropertiesCanBeSet(): void
    {
        $this->betalning->id = 1;
        $this->betalning->medlem_id = 2;
        $this->betalning->belopp = 100.50;
        $this->betalning->datum = '2023-05-01';
        $this->betalning->avser_ar = 2023;
        $this->betalning->kommentar = 'Test payment';
        $this->betalning->created_at = '2023-05-01 10:00:00';
        $this->betalning->updated_at = '2023-05-01 10:00:00';

        $this->assertEquals(1, $this->betalning->id);
        $this->assertEquals(2, $this->betalning->medlem_id);
        $this->assertEquals(100.50, $this->betalning->belopp);
        $this->assertEquals('2023-05-01', $this->betalning->datum);
        $this->assertEquals(2023, $this->betalning->avser_ar);
        $this->assertEquals('Test payment', $this->betalning->kommentar);
        $this->assertEquals('2023-05-01 10:00:00', $this->betalning->created_at);
        $this->assertEquals('2023-05-01 10:00:00', $this->betalning->updated_at);
    }

    public function testDefaultValues(): void
    {
        $betalning = new Betalning();
        
        $this->assertEquals('', $betalning->kommentar);
        $this->assertEquals('', $betalning->created_at);
        $this->assertEquals('', $betalning->updated_at);
    }
}
