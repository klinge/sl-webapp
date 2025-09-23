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

    public function isMemberOnSegling(int $seglingId, int $memberId): bool
    {
        $query = "SELECT COUNT(*) FROM Segling_Medlem_Roll WHERE segling_id = :segling_id AND medlem_id = :medlem_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segling_id', $seglingId, PDO::PARAM_INT);
        $stmt->bindParam(':medlem_id', $memberId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function addMemberToSegling(int $seglingId, int $memberId, ?int $roleId = null): bool
    {
        if ($roleId && $roleId !== 999) {
            $query = "INSERT INTO Segling_Medlem_Roll (segling_id, medlem_id, roll_id) VALUES (:segling_id, :medlem_id, :roll_id)";
        } else {
            $query = "INSERT INTO Segling_Medlem_Roll (segling_id, medlem_id) VALUES (:segling_id, :medlem_id)";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segling_id', $seglingId, PDO::PARAM_INT);
        $stmt->bindParam(':medlem_id', $memberId, PDO::PARAM_INT);
        if ($roleId && $roleId !== 999) {
            $stmt->bindParam(':roll_id', $roleId, PDO::PARAM_INT);
        }

        return $stmt->execute() && $stmt->rowCount() === 1;
    }

    public function removeMemberFromSegling(int $seglingId, int $memberId): bool
    {
        $query = "DELETE FROM Segling_Medlem_Roll WHERE segling_id = :segling_id AND medlem_id = :medlem_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segling_id', $seglingId, PDO::PARAM_INT);
        $stmt->bindParam(':medlem_id', $memberId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getById(int $id): ?Segling
    {
        try {
            return new Segling($this->conn, $this->logger, $id);
        } catch (Exception $e) {
            return null;
        }
    }

    public function createSegling(array $data): ?int
    {
        $segling = new Segling($this->conn, $this->logger);
        $segling->start_dat = $data['startdat'];
        $segling->slut_dat = $data['slutdat'];
        $segling->skeppslag = $data['skeppslag'];
        $segling->kommentar = $data['kommentar'] ?? null;
        return $segling->create();
    }

    public function updateSegling(int $id, array $data): bool
    {
        try {
            $segling = new Segling($this->conn, $this->logger, $id);
            $segling->start_dat = $data['startdat'];
            $segling->slut_dat = $data['slutdat'];
            $segling->skeppslag = $data['skeppslag'];
            $segling->kommentar = $data['kommentar'] ?? null;
            return $segling->save();
        } catch (Exception $e) {
            return false;
        }
    }

    public function deleteSegling(int $id): bool
    {
        try {
            $segling = new Segling($this->conn, $this->logger, $id);
            return $segling->delete();
        } catch (Exception $e) {
            return false;
        }
    }
}
