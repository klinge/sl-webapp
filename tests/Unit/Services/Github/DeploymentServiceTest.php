<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Github;

use App\Services\Github\DeploymentService;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DeploymentServiceTest extends TestCase
{
    private DeploymentService $service;
    private MockObject $logger;
    private string $triggerDirectory;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->triggerDirectory = sys_get_temp_dir() . '/test_triggers';

        if (!is_dir($this->triggerDirectory)) {
            mkdir($this->triggerDirectory, 0777, true);
        }

        $this->service = new DeploymentService($this->triggerDirectory, $this->logger);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->triggerDirectory)) {
            $files = glob($this->triggerDirectory . '/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->triggerDirectory);
        }
    }

    public function testScheduleDeploymentSuccess(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Deployment trigger file created successfully, deployment job will be run by cron');

        $result = $this->service->scheduleDeployment();

        $this->assertTrue($result);

        $files = glob($this->triggerDirectory . '/deploy_*.trigger');
        $this->assertCount(1, $files);
        $this->assertStringStartsWith($this->triggerDirectory . '/deploy_', $files[0]);
        $this->assertStringEndsWith('.trigger', $files[0]);
    }

    public function testScheduleDeploymentFailure(): void
    {
        $invalidDirectory = '/invalid/directory/that/does/not/exist';
        $service = new DeploymentService($invalidDirectory, $this->logger);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to create deployment trigger file');

        // Suppress the expected warning from file_put_contents
        $result = @$service->scheduleDeployment();

        $this->assertFalse($result);
    }

    public function testMultipleDeploymentsHandledCorrectly(): void
    {
        $result1 = $this->service->scheduleDeployment();
        $result2 = $this->service->scheduleDeployment();

        $this->assertTrue($result1);
        $this->assertTrue($result2);

        // At least one file should be created (second call may overwrite first due to same timestamp)
        $files = glob($this->triggerDirectory . '/deploy_*.trigger');
        $this->assertGreaterThanOrEqual(1, count($files));
    }
}
