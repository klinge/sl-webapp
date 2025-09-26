<?php

declare(strict_types=1);

namespace App\Services\Github;

use Monolog\Logger;

class GitRepositoryService
{
    public function __construct(
        private string $repoBaseDirectory,
        private Logger $logger
    ) {
    }

    /**
     * @return array{status: string, message: string}
     */
    public function updateRepository(string $branch, string $repoUrl): array
    {
        $cloneDir = $this->repoBaseDirectory . '/' . basename($repoUrl, '.git');
        $this->logger->debug("Fetching github repo to directory: " . $cloneDir);

        if (!is_dir($cloneDir)) {
            $result = $this->cloneRepository($repoUrl, $cloneDir);
            if ($result['status'] !== 'success') {
                return $result;
            }
        } else {
            $result = $this->fetchRepository($cloneDir);
            if ($result['status'] !== 'success') {
                return $result;
            }
        }

        $result = $this->checkoutBranch($cloneDir, $branch);
        if ($result['status'] !== 'success') {
            return $result;
        }

        $result = $this->pullLatestChanges($cloneDir, $branch);
        if ($result['status'] !== 'success') {
            return $result;
        }

        $this->logger->info("Successfully updated and checked out branch $branch");
        return ['status' => 'success', 'message' => 'Repository operations completed successfully'];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function cloneRepository(string $repoUrl, string $cloneDir): array
    {
        $escapedRepoUrl = escapeshellarg($repoUrl);
        $escapedCloneDir = escapeshellarg($cloneDir);

        exec("git clone $escapedRepoUrl $escapedCloneDir 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->logger->error("Failed to clone repository: " . implode(",", $output));
            return ['status' => 'error', 'message' => 'Failed to clone repository'];
        }
        return ['status' => 'success', 'message' => 'Repository cloned successfully'];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function fetchRepository(string $cloneDir): array
    {
        $escapedCloneDir = escapeshellarg($cloneDir);

        exec("git -C $escapedCloneDir fetch --all 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->logger->error("Failed to fetch latest changes: " . implode(",", $output));
            return ['status' => 'error', 'message' => 'Failed to fetch latest changes'];
        }
        return ['status' => 'success', 'message' => 'Repository fetched successfully'];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkoutBranch(string $cloneDir, string $branch): array
    {
        $escapedCloneDir = escapeshellarg($cloneDir);
        $escapedBranch = escapeshellarg($branch);

        exec("git -C $escapedCloneDir checkout $escapedBranch 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->logger->error("Failed to checkout branch $branch: " . implode(",", $output));
            return ['status' => 'error', 'message' => 'Failed to checkout branch'];
        }
        return ['status' => 'success', 'message' => 'Branch checked out successfully'];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function pullLatestChanges(string $cloneDir, string $branch): array
    {
        $escapedCloneDir = escapeshellarg($cloneDir);
        $escapedBranch = escapeshellarg($branch);

        $this->logger->debug("Checking out and pulling changes for branch: " . $branch);
        exec("git -C $escapedCloneDir pull origin $escapedBranch 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->logger->error("Failed to pull latest changes for branch $branch: " . implode("\n", $output));
            return ['status' => 'error', 'message' => 'Failed to pull latest changes'];
        }
        return ['status' => 'success', 'message' => 'Latest changes pulled successfully'];
    }
}
