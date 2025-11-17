<?php

declare(strict_types=1);

namespace App\Services\Github;

class GitHubService
{
    private const REPOSITORY_ID = 781366756;
    private const RELEASE_BRANCH_PATTERN = '/^release\/v\d+(\.\d+)?$/';

    /**
     * Initialize GitHubService with webhook secret for signature validation.
     *
     * @param string $webhookSecret Secret key for validating GitHub webhook signatures
     */
    public function __construct(private string $webhookSecret)
    {
    }

    /**
     * Validate GitHub webhook signature for security.
     *
     * @param string $rawRequestBody The raw request body from GitHub
     * @param string $signature The signature header from GitHub (without 'sha256=' prefix)
     * @return bool True if signature is valid, false otherwise
     */
    public function validateSignature(string $rawRequestBody, string $signature): bool
    {
        $calculatedSignature = hash_hmac('sha256', $rawRequestBody, $this->webhookSecret, false);
        return hash_equals($calculatedSignature, $signature);
    }

    /**
     * Check if webhook payload is from the expected repository.
     *
     * @param array<string, mixed> $payload GitHub webhook payload data
     * @return bool True if payload is from the correct repository
     */
    public function isValidRepository(array $payload): bool
    {
        return isset($payload['repository']['id']) && $payload['repository']['id'] === self::REPOSITORY_ID;
    }

    /**
     * Check if branch name matches release branch pattern.
     *
     * @param string $branch The branch name to check
     * @return bool True if branch follows release pattern (release/vX.Y)
     */
    public function isReleaseBranch(string $branch): bool
    {
        return preg_match(self::RELEASE_BRANCH_PATTERN, $branch) === 1;
    }

    /**
     * Extract branch name from Git reference.
     *
     * @param string $ref Git reference (e.g., 'refs/heads/main')
     * @return string The branch name without 'refs/heads/' prefix
     */
    public function extractBranchName(string $ref): string
    {
        return str_replace('refs/heads/', '', $ref);
    }
}
