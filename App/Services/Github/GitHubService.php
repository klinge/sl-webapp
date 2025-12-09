<?php

declare(strict_types=1);

namespace App\Services\Github;

/**
 * GitHub webhook validation service.
 *
 * Provides security validation for GitHub webhook requests:
 * - HMAC-SHA256 signature verification using shared secret
 * - Repository ID validation (only accepts repo 781366756)
 * - Release branch pattern matching (release/vX.Y format)
 *
 * Used by WebhookController to ensure only authentic requests
 * from the correct repository trigger deployments.
 */
class GitHubService
{
    /** @var int Expected GitHub repository ID */
    private const REPOSITORY_ID = 781366756;

    /** @var string Regex pattern for release branches (e.g., release/v1.0, release/v2.5) */
    private const RELEASE_BRANCH_PATTERN = '/^release\/v\d+(\.\d+)?$/';

    /**
     * Initialize service with webhook secret.
     *
     * @param string $webhookSecret Shared secret configured in GitHub webhook settings
     */
    public function __construct(private string $webhookSecret)
    {
    }

    /**
     * Validate webhook signature using HMAC-SHA256.
     *
     * Computes HMAC-SHA256 hash of request body using webhook secret
     * and compares with GitHub's signature using timing-safe comparison.
     *
     * @param string $rawRequestBody Raw HTTP request body from GitHub
     * @param string $signature Signature from X-Hub-Signature-256 header (without 'sha256=' prefix)
     * @return bool True if signature matches, false otherwise
     */
    public function validateSignature(string $rawRequestBody, string $signature): bool
    {
        $calculatedSignature = hash_hmac('sha256', $rawRequestBody, $this->webhookSecret, false);
        return hash_equals($calculatedSignature, $signature);
    }

    /**
     * Verify webhook is from expected repository.
     *
     * Checks repository.id in payload matches REPOSITORY_ID constant.
     * Prevents processing webhooks from other repositories.
     *
     * @param array<string, mixed> $payload Decoded GitHub webhook payload
     * @return bool True if repository ID matches expected value (781366756)
     */
    public function isValidRepository(array $payload): bool
    {
        return isset($payload['repository']['id']) && $payload['repository']['id'] === self::REPOSITORY_ID;
    }

    /**
     * Check if branch matches release pattern.
     *
     * Only branches matching release/vX.Y pattern trigger deployment.
     * Examples: release/v1.0, release/v2.5, release/v10.15
     * Non-matches: main, develop, release/test, v1.0
     *
     * @param string $branch Branch name to validate
     * @return bool True if branch matches release/vX.Y pattern
     */
    public function isReleaseBranch(string $branch): bool
    {
        return preg_match(self::RELEASE_BRANCH_PATTERN, $branch) === 1;
    }

    /**
     * Extract branch name from Git ref.
     *
     * GitHub webhook payload includes full ref path.
     * Strips 'refs/heads/' prefix to get branch name.
     *
     * Example: 'refs/heads/release/v1.0' → 'release/v1.0'
     *
     * @param string $ref Full Git reference from webhook payload
     * @return string Branch name without refs/heads/ prefix
     */
    public function extractBranchName(string $ref): string
    {
        return str_replace('refs/heads/', '', $ref);
    }
}
