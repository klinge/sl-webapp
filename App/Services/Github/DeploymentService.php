<?php

declare(strict_types=1);

namespace App\Services\Github;

use Monolog\Logger;

/**
 * Deployment scheduling service using trigger files.
 *
 * Implements the second stage of the two-stage deployment workflow.
 * Creates trigger files that signal a cron job to copy the staging
 * repository to the production web directory.
 *
 * Workflow:
 * 1. WebhookController updates staging repository
 * 2. This service creates trigger file: deploy_{timestamp}.trigger
 * 3. Cron job monitors trigger directory
 * 4. Cron job copies staging to production and removes trigger file
 *
 * This decouples webhook processing from actual deployment,
 * allowing the webhook to respond quickly while deployment
 * happens asynchronously.
 */
class DeploymentService
{
    /**
     * Initialize service with trigger file directory.
     *
     * @param string $triggerFileDirectory Directory where trigger files are created
     * @param Logger $logger Monolog logger for operation tracking
     */
    public function __construct(
        private string $triggerFileDirectory,
        private Logger $logger
    ) {
    }

    /**
     * Schedule deployment by creating trigger file.
     *
     * Creates empty file named deploy_{timestamp}.trigger in configured directory.
     * Cron job monitors this directory and deploys when trigger file appears.
     *
     * Example filename: deploy_1704067200.trigger
     *
     * @return bool True if trigger file created successfully, false on failure
     */
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
