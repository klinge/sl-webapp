<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class Roll extends BaseModel
{
    // object properties
    public int $id;
    public string $roll_namn;
    public string $kommentar;
    public string $created_at;
    public string $updated_at;

    public function __construct()
    {
        // Pure data object - no dependencies
    }

    public function getRollNamn(): string
    {
        return $this->roll_namn;
    }
}
