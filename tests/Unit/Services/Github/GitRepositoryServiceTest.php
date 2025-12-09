<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Github;

use App\Services\Github\GitRepositoryService;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

class GitRepositoryServiceTest extends TestCase
{
    private GitRepositoryService $service;
    private MockObject $logger;
    private string $repoBaseDirectory;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->repoBaseDirectory = '/tmp/test-repos';

        $this->service = new GitRepositoryService($this->repoBaseDirectory, $this->logger);
    }

    public function testServiceInstantiation(): void
    {
        $this->assertInstanceOf(GitRepositoryService::class, $this->service);
    }

    public function testUpdateRepositoryLogsDebugMessage(): void
    {
        $branch = 'main';
        $repoUrl = 'https://github.com/test/repo.git';

        $this->logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Fetching github repo to directory'));

        // This will fail due to invalid repo, but we're testing the debug logging
        $this->service->updateRepository($branch, $repoUrl);
    }

    public function testUpdateRepositoryReturnsErrorStructure(): void
    {
        $branch = 'main';
        $repoUrl = 'invalid-url';

        $result = $this->service->updateRepository($branch, $repoUrl);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('error', $result['status']);
    }

    public function testRejectsInvalidRepositoryUrl(): void
    {
        $branch = 'release/v1.0';
        $invalidUrl = 'https://evil.com/malicious.git';

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid repository URL format'));

        $result = $this->service->updateRepository($branch, $invalidUrl);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid repository URL', $result['message']);
    }

    public function testRejectsUrlWithCommandInjection(): void
    {
        $branch = 'release/v1.0';
        $maliciousUrl = 'https://github.com/test/repo.git; rm -rf /';

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid repository URL format'));

        $result = $this->service->updateRepository($branch, $maliciousUrl);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid repository URL', $result['message']);
    }

    public function testRejectsUrlWithPathTraversal(): void
    {
        $branch = 'release/v1.0';
        $maliciousUrl = 'https://github.com/../../../etc/passwd';

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid repository URL format'));

        $result = $this->service->updateRepository($branch, $maliciousUrl);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid repository URL', $result['message']);
    }

    public function testRejectsNonGitHubUrl(): void
    {
        $branch = 'release/v1.0';
        $nonGitHubUrl = 'https://gitlab.com/test/repo.git';

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid repository URL format'));

        $result = $this->service->updateRepository($branch, $nonGitHubUrl);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid repository URL', $result['message']);
    }

    public function testRejectsHttpUrl(): void
    {
        $branch = 'release/v1.0';
        $httpUrl = 'http://github.com/test/repo.git';

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid repository URL format'));

        $result = $this->service->updateRepository($branch, $httpUrl);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid repository URL', $result['message']);
    }

    public function testRejectsUrlWithSpecialCharacters(): void
    {
        $branch = 'release/v1.0';
        $specialCharsUrl = 'https://github.com/test$/repo@.git';

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid repository URL format'));

        $result = $this->service->updateRepository($branch, $specialCharsUrl);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid repository URL', $result['message']);
    }

    public function testAcceptsValidGitHubUrl(): void
    {
        $branch = 'release/v1.0';
        $validUrl = 'https://github.com/test-org/my-repo_123.git';

        $this->logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Fetching github repo to directory'));

        // Will fail at git operations but URL validation passes
        $result = $this->service->updateRepository($branch, $validUrl);

        // Should not be 'Invalid repository URL' error
        $this->assertNotEquals('Invalid repository URL', $result['message']);
    }
}
