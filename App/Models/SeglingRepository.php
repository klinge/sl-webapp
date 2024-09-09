<?php

namespace App\Models;

use PDO;
use Exception;

class SeglingRepository
{
    // database connection and table name
    private $conn;
    public $seglingar;

    public function __construct($db)
    {
        $this->conn = $db;
    }


    // Fetches all seglingar by querying Segling table in DB
    // Return: array of Segling objects
    public function getAll(): array
    {
        $withdeltagare = false;
        return $this->fetchAllSeglingar($withdeltagare);
    }

    // Fetches all seglingar by querying Segling table in DB
    // Return: array of Segling objects
    public function getAllWithDeltagare(): array
    {
        $withdeltagare = true;
        return $this->fetchAllSeglingar($withdeltagare);
    }
    //Private function that fetches seglingar with or without deltagare
    private function fetchAllSeglingar(bool $withdeltagare): array
    {
        $seglingar = [];
        $withdeltagare = $withdeltagare ? 'withdeltagare' : null;

        $query = "SELECT id from Segling ORDER BY startdatum DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as $sailevent) {
            try {
                $seglingar[] = new Segling($this->conn, $sailevent['id'], $withdeltagare);
            } catch (Exception $e) {
                //Do nothing right now..
            }
        }
        return $seglingar;
    }
}
