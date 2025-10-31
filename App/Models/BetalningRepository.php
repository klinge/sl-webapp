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

        $query = "SELECT * from Betalning ORDER BY datum DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($payments as $payment) {
            $betalning = $this->createBetalningFromData($payment);
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
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($payments as $payment) {
            $betalning = $this->createBetalningFromData($payment);
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
        return count($result) > 0;
    }

    /**
     * Creates a new payment.
     *
     * @param Betalning $betalning The payment to create
     * @return int The new payment ID
     */
    public function create(Betalning $betalning): int
    {
        $query = "INSERT INTO Betalning (medlem_id, belopp, datum, avser_ar, kommentar) VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $betalning->medlem_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $betalning->belopp, PDO::PARAM_STR);
        $stmt->bindParam(3, $betalning->datum, PDO::PARAM_STR);
        $stmt->bindParam(4, $betalning->avser_ar, PDO::PARAM_INT);
        $stmt->bindValue(5, $betalning->kommentar, PDO::PARAM_STR);

        $stmt->execute();
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Deletes a payment by ID.
     *
     * @param int $id Payment ID
     * @return bool Success status
     */
    public function deleteById(int $id): bool
    {
        $query = "DELETE FROM Betalning WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Gets a payment by ID.
     *
     * @param int $id Payment ID
     * @return Betalning|null The payment or null if not found
     */
    public function getById(int $id): ?Betalning
    {
        $query = "SELECT * FROM Betalning WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->createBetalningFromData($row);
    }

    /**
     * Creates a new empty Betalning object.
     *
     * @return Betalning New Betalning object
     */
    public function createNew(): Betalning
    {
        return new Betalning();
    }

    private function createBetalningFromData(array $data): Betalning
    {
        $betalning = new Betalning();
        $betalning->id = (int) $data['id'];
        $betalning->medlem_id = (int) $data['medlem_id'];
        $betalning->belopp = (float) $data['belopp'];
        $betalning->datum = $data['datum'];
        $betalning->avser_ar = (int) $data['avser_ar'];
        $betalning->kommentar = $data['kommentar'] ?? '';
        $betalning->created_at = $data['created_at'];
        $betalning->updated_at = $data['updated_at'];
        return $betalning;
    }
}
