<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Exception;
use Psr\Log\LoggerInterface;

class SeglingRepository extends BaseModel
{
    // object attributes
    public $seglingar;

    public function __construct(PDO $db, LoggerInterface $logger)
    {
        parent::__construct($db, $logger);
    }

    /**
     * Fetches all seglingar by querying Segling table in DB
     * The function takes no parameters and returns an array of all Segling objects
     *
     * @return array Segling Array of all seglingar
     */
    public function getAll(): array
    {
        $withdeltagare = false;
        return $this->fetchAllSeglingar($withdeltagare);
    }

    /**
     * Fetches all seglingar by querying Segling table in DB
     * The function takes no parameters and returns an array of all Segling objects including deltagare
     *
     * @return array Segling Array of all seglingar with deltagare details added
     */
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
                $seglingar[] = new Segling($this->conn, $this->logger, $sailevent['id'], $withdeltagare);
            } catch (Exception $e) {
                //Do nothing right now..
            }
        }
        return $seglingar;
    }
}
