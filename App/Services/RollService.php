<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RollRepository;
use App\Models\MedlemRepository;

class RollService
{
    public function __construct(
        private RollRepository $rollRepo,
        private MedlemRepository $medlemRepo
    ) {
    }

    public function getAllRoles(): array
    {
        return $this->rollRepo->getAll();
    }

    public function getMembersInRole(int $rollId): array
    {
        return $this->medlemRepo->getMembersByRollId($rollId);
    }
}