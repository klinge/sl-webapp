<?php

namespace Tests\Unit\Models;

use App\Models\MedlemRepository;
use App\Models\Medlem;
use PDO;
use Monolog\Logger;

class MedlemRepositoryFake extends MedlemRepository
{
    protected function createMedlem(PDO $conn, Logger $logger, int $id): Medlem
    {
        // Create a simple test double that extends Medlem
        // @codingStandardsIgnoreLine
        return new class($conn, $logger, $id) extends Medlem {
            public function __construct($conn, $logger, $id)
            {
                // Empty constructor to avoid database calls
            }
        };
    }
}
