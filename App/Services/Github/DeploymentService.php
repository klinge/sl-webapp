<?php

declare(strict_types=1);

namespace App\Services\Github;

use Monolog\Logger;

class DeploymentService
{
    public function __construct(
        private string $triggerFileDirectory,
        private Logger $logger
    ) {
    }

    public function scheduleDeployment(): bool
    {
        $triggerFile = $this->triggerFileDirectory . '/deploy_' . time() . '.trigger';
        $result = file_put_contents($triggerFile, '');

        if ($result === false) {
            $this->logger->error('Failed to create deployment trigger file');
            return false;
        }

        $this->logger->info('Deployment trigger file created successfully, deployment job will be run by cron');
        return true;
    }
}
