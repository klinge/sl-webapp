<?php

namespace Tests\Unit\Models;

use App\Models\MedlemRepository;
use App\Models\Medlem;
use PDO;
use Psr\Log\LoggerInterface;

class MedlemRepositoryFake extends MedlemRepository
{
    protected function createMedlem(int $id): Medlem
    {
        // Create a simple test double that extends Medlem
        // @codingStandardsIgnoreLine
        return new class ($id) extends Medlem {
            public function __construct($id)
            {
                // Empty constructor to avoid database calls
            }
        };
    }
}
