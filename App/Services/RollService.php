<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RollRepository;
use App\Models\MedlemRepository;

class RollService
{
    /**
     * Initialize RollService with required repositories.
     *
     * @param RollRepository $rollRepo Repository for role operations
     * @param MedlemRepository $medlemRepo Repository for member operations
     */
    public function __construct(
        private RollRepository $rollRepo,
        private MedlemRepository $medlemRepo
    ) {
    }

    /**
     * Retrieve all available roles.
     *
     * @return array<int, mixed> Array of all role records
     */
    public function getAllRoles(): array
    {
        return $this->rollRepo->getAll();
    }

    /**
     * Get all members assigned to a specific role.
     *
     * @param int $rollId The role ID to find members for
     * @return array<int, mixed> Array of member records with the specified role
     */
    public function getMembersInRole(int $rollId): array
    {
        return $this->medlemRepo->findMembersByRollId($rollId);
    }
}
