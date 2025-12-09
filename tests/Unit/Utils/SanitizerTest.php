<?php

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\Sanitizer;

class SanitizerTest extends TestCase
{
    private Sanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new Sanitizer();
    }

    public function testSanitizeString()
    {
        $input = ['name' => '<script>alert("xss")</script>'];
        $rules = ['name' => 'string'];

        $result = $this->sanitizer->sanitize($input, $rules);

        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $result['name']);
    }

    public function testSanitizeEmail()
    {
        $input = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];

        $result = $this->sanitizer->sanitize($input, $rules);

        $this->assertEquals('test@example.com', $result['email']);
    }

    public function testSanitizeInt()
    {
        $input = ['age' => '25abc'];
        $rules = ['age' => 'int'];

        $result = $this->sanitizer->sanitize($input, $rules);

        $this->assertEquals('25', $result['age']);
    }

    public function testSanitizeFloat()
    {
        $input = ['price' => '19.99abc'];
        $rules = ['price' => 'float'];

        $result = $this->sanitizer->sanitize($input, $rules);

        $this->assertEquals('1999', $result['price']);
    }

    public function testSanitizeUrl()
    {
        $input = ['website' => 'https://example.com'];
        $rules = ['website' => 'url'];

        $result = $this->sanitizer->sanitize($input, $rules);

        $this->assertEquals('https://example.com', $result['website']);
    }

    public function testSanitizeBool()
    {
        $input = ['active' => '1'];
        $rules = ['active' => 'bool'];

        $result = $this->sanitizer->sanitize($input, $rules);

        $this->assertTrue($result['active']);
    }

    public function testSanitizeDateWithDefaultFormat()
    {
        $input = ['birthdate' => '2024-12-09'];
        $rules = ['birthdate' => ['date', 'Y-m-d']];

        $result = $this->sanitizer->sanitize($input, $rules);

        $this->assertEquals('2024-12-09', $result['birthdate']);
    }

    public function testSanitizeDateWithCustomFormat()
    {
        $input = ['birthdate' => '09/12/2024'];
        $rules = ['birthdate' => ['date', 'd/m/Y']];

        $result = $this->sanitizer->sanitize($input, $rules);

        $this->assertEquals('09/12/2024', $result['birthdate']);
    }

    public function testSanitizeDateInvalidReturnsNull()
    {
        $input = ['birthdate' => 'invalid-date'];
        $rules = ['birthdate' => 'date'];

        $result = $this->sanitizer->sanitize($input, $rules);

        $this->assertNull($result['birthdate']);
    }

    public function testSanitizeMultipleFields()
    {
        $input = [
            'name' => '<b>John</b>',
            'email' => 'john@example.com',
            'age' => '30'
        ];
        $rules = [
            'name' => 'string',
            'email' => 'email',
            'age' => 'int'
        ];

        $result = $this->sanitizer->sanitize($input, $rules);

        $this->assertEquals('&lt;b&gt;John&lt;/b&gt;', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
        $this->assertEquals('30', $result['age']);
    }

    public function testSanitizeTrimsWhitespace()
    {
        $input = ['name' => '  John Doe  '];
        $rules = ['name' => 'string'];

        $result = $this->sanitizer->sanitize($input, $rules);

        $this->assertEquals('John Doe', $result['name']);
    }

    public function testSanitizeIgnoresMissingFields()
    {
        $input = ['name' => 'John'];
        $rules = ['name' => 'string', 'email' => 'email'];

        $result = $this->sanitizer->sanitize($input, $rules);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('email', $result);
    }

    public function testInvalidRuleThrowsException()
    {
        $input = ['field' => 'value'];
        $rules = ['field' => 'invalid_rule'];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Sanitization rule 'invalid_rule' does not exist.");

        $this->sanitizer->sanitize($input, $rules);
    }
}
