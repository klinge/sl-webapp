<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Github;

use App\Services\Github\GitRepositoryService;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

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
}
