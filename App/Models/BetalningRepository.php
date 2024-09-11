<?php

namespace App\Models;

use PDO;

class BetalningRepository
{
    // database connection and table name
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAll()
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

    public function getBetalningForMedlem(int $medlemId)
    {
        $betalningar = [];

        $query = "SELECT * from Betalning WHERE medlem_id = ? ORDER BY datum DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $medlemId, PDO::PARAM_INT);
        $stmt->execute();
        $payments =  $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($payments as $payment) {
            $betalning = new Betalning($this->conn, $payment);
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
