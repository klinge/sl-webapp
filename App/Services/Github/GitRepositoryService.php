<?php

declare(strict_types=1);

namespace App\Services\Github;

use Monolog\Logger;

/**
 * Git repository operations service for staging directory.
 *
 * Manages git operations to maintain an up-to-date staging copy
 * of the repository. This staging copy is later deployed to production
 * by a separate cron job.
 *
 * Operations:
 * - Clone repository if not present in staging directory
 * - Fetch latest changes from remote
 * - Checkout specified release branch
 * - Pull latest commits for the branch
 *
 * All git commands are executed via shell with proper escaping.
 * Errors are logged and returned as status arrays.
 */
class GitRepositoryService
{
    /**
     * Initialize service with staging directory path.
     *
     * @param string $repoBaseDirectory Base directory for staging repositories
     * @param Logger $logger Monolog logger for operation tracking
     */
    public function __construct(
        private string $repoBaseDirectory,
        private Logger $logger
    ) {
    }

    /**
     * Update staging repository to latest commit on specified branch.
     *
     * Orchestrates the complete update workflow:
     * 1. Validate repository URL format
     * 2. Clone repository if not present, or fetch if exists
     * 3. Checkout the target branch
     * 4. Pull latest changes from origin
     *
     * Repository is cloned/updated in: {repoBaseDirectory}/{repo-name}
     * Example: /var/staging/sl-webapp
     *
     * @param string $branch Branch name to checkout (e.g., 'release/v1.0')
     * @param string $repoUrl Git clone URL from webhook payload
     * @return array{status: string, message: string} Success or error status with message
     */
    public function updateRepository(string $branch, string $repoUrl): array
    {
        // Validate repository URL format to prevent command injection
        if (!$this->isValidRepositoryUrl($repoUrl)) {
            $this->logger->error("Invalid repository URL format: " . $repoUrl);
            return ['status' => 'error', 'message' => 'Invalid repository URL'];
        }

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
     * Validate repository URL format.
     *
     * Ensures URL is a valid GitHub HTTPS clone URL to prevent command injection.
     * Only allows alphanumeric characters, hyphens, and underscores in org/repo names.
     *
     * @param string $repoUrl Repository URL to validate
     * @return bool True if URL is valid GitHub HTTPS format
     */
    private function isValidRepositoryUrl(string $repoUrl): bool
    {
        return preg_match('#^https://github\.com/[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+\.git$#', $repoUrl) === 1;
    }

    /**
     * Clone repository to staging directory.
     *
     * Executes: git clone {repoUrl} {cloneDir}
     * Only called when repository doesn't exist in staging.
     *
     * @param string $repoUrl Git clone URL
     * @param string $cloneDir Target directory for clone
     * @return array{status: string, message: string} Success or error status
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
     * Fetch latest changes from all remotes.
     *
     * Executes: git -C {cloneDir} fetch --all
     * Updates remote tracking branches without modifying working directory.
     *
     * @param string $cloneDir Repository directory path
     * @return array{status: string, message: string} Success or error status
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
     * Checkout specified branch.
     *
     * Executes: git -C {cloneDir} checkout {branch}
     * Switches working directory to target branch.
     *
     * @param string $cloneDir Repository directory path
     * @param string $branch Branch name to checkout
     * @return array{status: string, message: string} Success or error status
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
     * Pull latest commits from origin for current branch.
     *
     * Executes: git -C {cloneDir} pull origin {branch}
     * Updates working directory with latest commits from remote.
     *
     * @param string $cloneDir Repository directory path
     * @param string $branch Branch name to pull
     * @return array{status: string, message: string} Success or error status
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
