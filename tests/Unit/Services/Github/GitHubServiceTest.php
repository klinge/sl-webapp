<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Github;

use App\Services\Github\GitHubService;
use PHPUnit\Framework\TestCase;

class GitHubServiceTest extends TestCase
{
    private GitHubService $service;
    private string $webhookSecret = 'test-secret';

    protected function setUp(): void
    {
        $this->service = new GitHubService($this->webhookSecret);
    }

    public function testValidateSignatureWithValidSignature(): void
    {
        $payload = '{"test": "data"}';
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret, false);

        $result = $this->service->validateSignature($payload, $expectedSignature);

        $this->assertTrue($result);
    }

    public function testValidateSignatureWithInvalidSignature(): void
    {
        $payload = '{"test": "data"}';
        $invalidSignature = 'invalid-signature';

        $result = $this->service->validateSignature($payload, $invalidSignature);

        $this->assertFalse($result);
    }

    public function testIsValidRepositoryWithCorrectId(): void
    {
        $payload = ['repository' => ['id' => 781366756]];

        $result = $this->service->isValidRepository($payload);

        $this->assertTrue($result);
    }

    public function testIsValidRepositoryWithIncorrectId(): void
    {
        $payload = ['repository' => ['id' => 123456789]];

        $result = $this->service->isValidRepository($payload);

        $this->assertFalse($result);
    }

    public function testIsReleaseBranchWithValidBranches(): void
    {
        $validBranches = [
            'release/v1',
            'release/v1.0',
            'release/v2.5',
            'release/v10.99'
        ];

        foreach ($validBranches as $branch) {
            $result = $this->service->isReleaseBranch($branch);
            $this->assertTrue($result, "Branch '$branch' should be valid");
        }
    }

    public function testIsReleaseBranchWithInvalidBranches(): void
    {
        $invalidBranches = [
            'main',
            'develop',
            'feature/test',
            'release/v',
            'release/v1.0.0',
            'release/1.0',
            'releases/v1.0'
        ];

        foreach ($invalidBranches as $branch) {
            $result = $this->service->isReleaseBranch($branch);
            $this->assertFalse($result, "Branch '$branch' should be invalid");
        }
    }

    public function testExtractBranchName(): void
    {
        $testCases = [
            'refs/heads/main' => 'main',
            'refs/heads/release/v1.0' => 'release/v1.0',
            'refs/heads/feature/test' => 'feature/test',
            'main' => 'main'
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->service->extractBranchName($input);
            $this->assertEquals($expected, $result);
        }
    }
}
