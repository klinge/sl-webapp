<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Roll
{
    // database connection and table name
    private $conn;
    private $table_name = "Roll";

    // object properties
    public int $id;
    public string $roll_namn;
    public string $kommentar;
    public string $created_at;
    public string $updated_at;

    public function __construct(PDO $db,)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
