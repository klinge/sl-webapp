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
     * Retrieves all roles as Roll objects.
     *
     * @return array<int, Roll> Array of Roll objects
     */
    public function getAll(): array
    {
        $query = "SELECT * FROM Roll";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $roles = [];
        foreach ($results as $row) {
            $roles[] = $this->createRollFromData($row);
        }
        return $roles;
    }

    /**
     * Creates a Roll object from database row data.
     *
     * @param array<string, mixed> $row Database row data
     * @return Roll Populated Roll object
     */
    private function createRollFromData(array $row): Roll
    {
        $roll = new Roll();
        $roll->id = (int) $row['id'];
        $roll->roll_namn = $row['roll_namn'];
        $roll->kommentar = $row['kommentar'];
        $roll->created_at = $row['created_at'];
        $roll->updated_at = $row['updated_at'];
        return $roll;
    }
}
