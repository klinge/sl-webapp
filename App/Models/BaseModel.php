<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

abstract class BaseModel
{
    // database connection and table name
    protected PDO $conn;
    protected LoggerInterface $logger;

    public function __construct(PDO $db, LoggerInterface $logger)
    {
        $this->conn = $db;
        $this->logger = $logger;
    }
}
