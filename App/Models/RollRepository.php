<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class RollRepository extends BaseModel
{
    public function __construct(PDO $db, LoggerInterface $logger)
    {
        parent::__construct($db, $logger);
    }

    /**
     * Retrieves all roles from the database.
     *
     * @return array Array of role data
     */
    public function getAll(): array
    {
        $roll = new Roll($this->conn, $this->logger);
        return $roll->getAll();
    }
}
