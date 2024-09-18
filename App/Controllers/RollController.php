<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\MedlemRepository;
use App\Models\Roll;

class RollController extends BaseController
{
    public function list()
    {
        $roll = new Roll($this->conn);
        $roller = $roll->getAll();
        $this->jsonResponse($roller);
        exit;
    }

    public function membersInRole(array $params)
    {
        $rollId = $params['id'];
        $medlemRepo = new MedlemRepository($this->conn);
        $result = $medlemRepo->getMembersByRollId($rollId);
        $this->jsonResponse($result);
        exit;
    }
}
