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

    /**
     * Retrieves all seglingar as Segling objects.
     *
     * @return array<int, Segling> Array of Segling objects
     */
    public function getAll(): array
    {
        $query = "SELECT * FROM Segling ORDER BY startdatum DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $seglingar = [];
        foreach ($results as $row) {
            $seglingar[] = $this->createSeglingFromData($row);
        }
        return $seglingar;
    }

    /**
     * Retrieves all seglingar with participants as Segling objects.
     *
     * @return array<int, Segling> Array of Segling objects with participants
     */
    public function getAllWithDeltagare(): array
    {
        $seglingar = $this->getAll();
        foreach ($seglingar as $segling) {
            $segling->deltagare = $this->findDeltagare($segling->id);
        }
        return $seglingar;
    }

    /**
     * Retrieves a segling by ID as Segling object.
     *
     * @param int $id Segling ID
     * @return Segling|null The Segling object or null if not found
     */
    public function getById(int $id): ?Segling
    {
        $query = "SELECT * FROM Segling WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->createSeglingFromData($row) : null;
    }

    /**
     * Retrieves a segling by ID with participants as Segling object.
     *
     * @param int $id Segling ID
     * @return Segling|null The Segling object with participants or null if not found
     */
    public function getByIdWithDeltagare(int $id): ?Segling
    {
        $segling = $this->getById($id);
        if ($segling) {
            $segling->deltagare = $this->findDeltagare($id);
        }
        return $segling;
    }

    /**
     * Creates a new segling.
     *
     * @param array<string, mixed> $data Segling data
     * @return int|null The new segling ID or null on failure
     */
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

    /**
     * Updates an existing segling.
     *
     * @param int $id Segling ID
     * @param array<string, mixed> $data Segling data
     * @return bool Success status
     */
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

    /**
     * Deletes a segling by ID.
     *
     * @param int $id Segling ID
     * @return bool Success status
     */
    public function delete(int $id): bool
    {
        $query = "DELETE FROM Segling WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() === 1;
    }

    /**
     * Finds participants for a segling as raw data.
     *
     * @param int $seglingId Segling ID
     * @return array<int, array<string, mixed>> Array of participant data
     */
    public function findDeltagare(int $seglingId): array
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

    /**
     * Finds participants for a segling as raw data.
     *
     * @deprecated Use findDeltagare() instead
     * @param int $seglingId Segling ID
     * @return array<int, array<string, mixed>> Array of participant data
     */
    public function getDeltagare(int $seglingId): array
    {
        return $this->findDeltagare($seglingId);
    }

    /**
     * Checks if a member is participating in a segling.
     *
     * @param int $seglingId Segling ID
     * @param int $memberId Member ID
     * @return bool True if member is participating
     */
    public function isMemberOnSegling(int $seglingId, int $memberId): bool
    {
        $query = "SELECT COUNT(*) FROM Segling_Medlem_Roll WHERE segling_id = :segling_id AND medlem_id = :medlem_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segling_id', $seglingId, PDO::PARAM_INT);
        $stmt->bindParam(':medlem_id', $memberId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Adds a member to a segling with optional role.
     *
     * @param int $seglingId Segling ID
     * @param int $memberId Member ID
     * @param int|null $roleId Optional role ID
     * @return bool Success status
     */
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

    /**
     * Removes a member from a segling.
     *
     * @param int $seglingId Segling ID
     * @param int $memberId Member ID
     * @return bool Success status
     */
    public function removeMemberFromSegling(int $seglingId, int $memberId): bool
    {
        $query = "DELETE FROM Segling_Medlem_Roll WHERE segling_id = :segling_id AND medlem_id = :medlem_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segling_id', $seglingId, PDO::PARAM_INT);
        $stmt->bindParam(':medlem_id', $memberId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Creates a Segling object from database row data.
     *
     * @param array<string, mixed> $row Database row data
     * @return Segling Populated Segling object
     */
    private function createSeglingFromData(array $row): Segling
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

    /**
     * @deprecated Use create() instead
     * @param array<string, mixed> $data Segling data
     * @return int|null The new segling ID or null on failure
     */
    public function createSegling(array $data): ?int
    {
        return $this->create($data);
    }

    /**
     * @deprecated Use update() instead
     * @param int $id Segling ID
     * @param array<string, mixed> $data Segling data
     * @return bool Success status
     */
    public function updateSegling(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * @deprecated Use delete() instead
     * @param int $id Segling ID
     * @return bool Success status
     */
    public function deleteSegling(int $id): bool
    {
        return $this->delete($id);
    }
}
