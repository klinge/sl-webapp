<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class SeglingRepository extends BaseModel
{
    public function __construct(PDO $db, LoggerInterface $logger)
    {
        parent::__construct($db, $logger);
    }

    public function getAll(): array
    {
        $query = "SELECT * FROM Segling ORDER BY startdatum DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $seglingar = [];
        foreach ($results as $row) {
            $seglingar[] = $this->mapRowToSegling($row);
        }
        return $seglingar;
    }

    public function getAllWithDeltagare(): array
    {
        $seglingar = $this->getAll();
        foreach ($seglingar as $segling) {
            $segling->deltagare = $this->getDeltagare($segling->id);
        }
        return $seglingar;
    }

    public function getById(int $id): ?Segling
    {
        $query = "SELECT * FROM Segling WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToSegling($row) : null;
    }

    public function getByIdWithDeltagare(int $id): ?Segling
    {
        $segling = $this->getById($id);
        if ($segling) {
            $segling->deltagare = $this->getDeltagare($id);
        }
        return $segling;
    }

    public function create(array $data): ?int
    {
        $query = 'INSERT INTO Segling (startdatum, slutdatum, skeppslag, kommentar) VALUES (:startdat, :slutdat, :skeppslag, :kommentar)';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':startdat', $data['startdat'], PDO::PARAM_STR);
        $stmt->bindParam(':slutdat', $data['slutdat'], PDO::PARAM_STR);
        $stmt->bindParam(':skeppslag', $data['skeppslag'], PDO::PARAM_STR);
        $stmt->bindValue(':kommentar', $data['kommentar'] ?? null, PDO::PARAM_STR);
        
        if ($stmt->execute() && $stmt->rowCount() === 1) {
            return (int) $this->conn->lastInsertId();
        }
        return null;
    }

    public function update(int $id, array $data): bool
    {
        $query = "UPDATE Segling SET 
            startdatum = :startdatum, 
            slutdatum = :slutdatum, 
            skeppslag = :skeppslag, 
            kommentar = :kommentar
            WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':startdatum', $data['startdat'], PDO::PARAM_STR);
        $stmt->bindParam(':slutdatum', $data['slutdat'], PDO::PARAM_STR);
        $stmt->bindParam(':skeppslag', $data['skeppslag'], PDO::PARAM_STR);
        $stmt->bindValue(':kommentar', $data['kommentar'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute() && $stmt->rowCount() === 1;
    }

    public function delete(int $id): bool
    {
        $query = "DELETE FROM Segling WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() === 1;
    }

    public function getDeltagare(int $seglingId): array
    {
        $query = "SELECT smr.medlem_id, m.fornamn, m.efternamn, smr.roll_id, r.roll_namn
                    FROM Segling_Medlem_Roll smr
                    JOIN Medlem m ON smr.medlem_id = m.id
                    LEFT JOIN Roll r ON smr.roll_id = r.id
                    WHERE smr.segling_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $seglingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    private function mapRowToSegling(array $row): Segling
    {
        return new Segling(
            id: (int) $row['id'],
            start_dat: $row['startdatum'],
            slut_dat: $row['slutdatum'],
            skeppslag: $row['skeppslag'],
            kommentar: $row['kommentar'],
            deltagare: [],
            created_at: $row['created_at'],
            updated_at: $row['updated_at']
        );
    }

    // Legacy methods for backward compatibility
    public function createSegling(array $data): ?int
    {
        return $this->create($data);
    }

    public function updateSegling(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteSegling(int $id): bool
    {
        return $this->delete($id);
    }
}