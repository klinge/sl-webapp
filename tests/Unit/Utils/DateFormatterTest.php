<?php

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\DateFormatter;

class DateFormatterTest extends TestCase
{
    public function testFormatDateWithHmsValidDate(): void
    {
        $result = DateFormatter::formatDateWithHms('2023-12-24 15:30:45');
        $this->assertEquals('2023-12-24', $result);
    }

    public function testFormatDateWithHmsInvalidDate(): void
    {
        $result = DateFormatter::formatDateWithHms('invalid-date');
        $this->assertNull($result);
    }

    public function testFormatDateWithHmsIncorrectFormat(): void
    {
        $result = DateFormatter::formatDateWithHms('2023-13-45 25:70:99');
        $this->assertNull($result);
    }

    public function testFormatDateYmdValidDate(): void
    {
        $result = DateFormatter::formatDateYmd('2023-12-24');
        $this->assertEquals('2023-12-24', $result);
    }

    public function testFormatDateYmdInvalidDate(): void
    {
        $result = DateFormatter::formatDateYmd('invalid-date');
        $this->assertNull($result);
    }

    public function testFormatDateYmdIncorrectFormat(): void
    {
        $result = DateFormatter::formatDateYmd('2023-13-45');
        $this->assertNull($result);
    }
}
