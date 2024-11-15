<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\MedlemDataValidatorService;
use App\Models\Medlem;
use App\Utils\Session;
use PDO;

class MedlemDataValidatorServiceTest extends TestCase
{
    private MedlemDataValidatorService $validator;
    private Medlem $medlem;

    protected function setUp(): void
    {
        $this->validator = new MedlemDataValidatorService();

        // Create a mock PDO and logger for Medlem
        $pdo = $this->createMock(PDO::class);
        $logger = $this->createMock(\Monolog\Logger::class);
        $this->medlem = new Medlem($pdo, $logger);
    }

    public function testValidateAndPrepareWithValidData(): void
    {
        $postData = [
            'fornamn' => 'Test',
            'efternamn' => 'Person',
            'fodelsedatum' => '1990-01-01',
            'email' => 'test@example.com',
            'roller' => ['1', '2']
        ];

        $result = $this->validator->validateAndPrepare($this->medlem, $postData);

        $this->assertTrue($result);
        $this->assertEquals('Test', $this->medlem->fornamn);
        $this->assertEquals('test@example.com', $this->medlem->email);
    }

    public function testValidateAndPrepareWithMissingRequiredFields(): void
    {
        $postData = [
            'email' => 'test@example.com',
            'roller' => ['1']
        ];

        $result = $this->validator->validateAndPrepare($this->medlem, $postData);

        $this->assertFalse($result);
    }

    public function testBooleanFieldsDefaultToFalseWhenNotSet(): void
    {
        $postData = [
            'fornamn' => 'Test',
            'efternamn' => 'Person',
            'fodelsedatum' => '1990-01-01'
        ];

        $this->validator->validateAndPrepare($this->medlem, $postData);

        $this->assertFalse($this->medlem->isAdmin);
        $this->assertFalse($this->medlem->godkant_gdpr);
    }

    public function testEmailChangeDetection(): void
    {
        // Set initial email
        $this->medlem->email = 'old@example.com';

        $postData = [
            'fornamn' => 'Test',
            'efternamn' => 'Person',
            'fodelsedatum' => '1990-01-01',
            'email' => 'new@example.com'
        ];

        $this->validator->validateAndPrepare($this->medlem, $postData);

        $this->assertTrue($this->validator->hasEmailChanged());
    }

    public function testNoEmailChangeDetection(): void
    {
        // Set initial email
        $this->medlem->email = 'same@example.com';

        $postData = [
            'fornamn' => 'Test',
            'efternamn' => 'Person',
            'fodelsedatum' => '1990-01-01',
            'email' => 'same@example.com'
        ];

        $this->validator->validateAndPrepare($this->medlem, $postData);

        $this->assertFalse($this->validator->hasEmailChanged());
    }
}
