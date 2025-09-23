<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class BetalningRepository extends BaseModel
{
    public function __construct($db, LoggerInterface $logger)
    {
        parent::__construct($db, $logger);
    }

    public function getAll(): array
    {
        $betalningar = [];

        $query = "SELECT * from Betalning";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $payments =  $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($payments as $payment) {
            $betalning = new Betalning($this->conn, $payment);
            $betalningar[] = $betalning;
        }
        return $betalningar;
    }

    public function getAllWithName(): array
    {
        $betalningar = [];

        $query = "SELECT b.*, m.fornamn, m.efternamn 
             FROM Betalning b 
             LEFT JOIN Medlem m ON b.medlem_id = m.id 
             ORDER BY b.datum DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $betalningar =  $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $betalningar;
    }

    public function getBetalningForMedlem(int $medlemId): array
    {
        $betalningar = [];

        $query = "SELECT * from Betalning WHERE medlem_id = ? ORDER BY datum DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $medlemId, PDO::PARAM_INT);
        $stmt->execute();
        $payments =  $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($payments as $payment) {
            $betalning = new Betalning($this->conn, $this->logger, $payment);
            $betalningar[] = $betalning;
        }
        return $betalningar;
    }

    public function memberHasPayed(int $medlemId, int $year): bool
    {
        $query = "SELECT * from Betalning WHERE medlem_id = :id AND avser_ar = :ar";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $medlemId, PDO::PARAM_INT);
        $stmt->bindParam(':ar', $year, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            return true;
        }
        return false;
    }
}
