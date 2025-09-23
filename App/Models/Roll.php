<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class Roll extends BaseModel
{
    private $table_name = "Roll";
    // object properties
    public int $id;
    public string $roll_namn;
    public string $kommentar;
    public string $created_at;
    public string $updated_at;

    public function __construct(PDO $db, LoggerInterface $logger)
    {
        parent::__construct($db, $logger);
    }

    public function getAll(): array
    {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
