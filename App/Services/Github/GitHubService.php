<?php

declare(strict_types=1);

namespace App\Services\Github;

class GitHubService
{
    private const REPOSITORY_ID = 781366756;
    private const RELEASE_BRANCH_PATTERN = '/^release\/v\d+(\.\d+)?$/';

    public function __construct(private string $webhookSecret)
    {
    }

    public function validateSignature(string $rawRequestBody, string $signature): bool
    {
        $calculatedSignature = hash_hmac('sha256', $rawRequestBody, $this->webhookSecret, false);
        return hash_equals($calculatedSignature, $signature);
    }

    public function isValidRepository(array $payload): bool
    {
        return isset($payload['repository']['id']) && $payload['repository']['id'] === self::REPOSITORY_ID;
    }

    public function isReleaseBranch(string $branch): bool
    {
        return preg_match(self::RELEASE_BRANCH_PATTERN, $branch) === 1;
    }

    public function extractBranchName(string $ref): string
    {
        return str_replace('refs/heads/', '', $ref);
    }
}
