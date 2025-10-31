<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Exception;
use Psr\Log\LoggerInterface;

class MedlemRepository extends BaseModel
{
    public $medlemmar;

    public function __construct(PDO $db, LoggerInterface $logger)
    {
        parent::__construct($db, $logger);
    }


    /**
     * Retrieves all members from the database.
     *
     * Fetches member and creates Medlem objects for each,
     * and returns them in an array sorted by last name.
     *
     * @return array Medlem[] An array of Medlem objects
     */
    public function getAll(): array
    {
        $medlemmar = [];

        $query = "SELECT id FROM Medlem ORDER BY efternamn ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $members =  $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($members as $member) {
            try {
                $medlem = $this->createMedlem($member['id']);
                $medlemmar[] = $medlem;
            } catch (Exception $e) {
                //Do nothing right now..
            }
        }
        return $medlemmar;
    }

    // Find all Medlemmar in a role by querying Medlem, Roll, and Medlem_Roll tables
    // to find members with a specified roll_namn
    public function getMembersByRollName(string $rollName): array
    {
        $query = "SELECT m.id,m.fornamn, m.efternamn, r.roll_namn
            FROM  Medlem m
            INNER JOIN Medlem_Roll mr ON mr.medlem_id = m.id
            INNER JOIN Roll r ON r.id = mr.roll_id
            WHERE r.roll_namn = :rollnamn
            ORDER BY m.efternamn ASC;";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rollnamn', $rollName);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Query Medlem, Roll, and Medlem_Roll tables
    // to find members with a specified roll_namn
    public function getMembersByRollId(int $rollId): array
    {
        $query = "SELECT m.id,m.fornamn, m.efternamn, r.id AS roll_id, r.roll_namn
            FROM  Medlem m
            INNER JOIN Medlem_Roll mr ON mr.medlem_id = m.id
            INNER JOIN Roll r ON r.id = mr.roll_id
            WHERE r.id = :id
            ORDER BY m.fornamn ASC;";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $rollId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves member data by email.
     *
     * @param string $email The email address of the member
     * @return array|bool Member data array or false if not found
     */
    public function getMemberByEmail(string $email): array|bool
    {
        $stmt = $this->conn->prepare("SELECT * FROM medlem WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: false;
    }

    /**
     * Retrieves member email addresses.
     *
     * @return array An array of member email addresses
     */
    public function getEmailForActiveMembers(): array
    {
        $query = "SELECT email FROM medlem WHERE pref_kommunikation = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //Remove rows with empty emails
        return array_filter($result, fn($item) => !empty($item['email']));
    }

    /**
     * Finds member data by ID.
     *
     * @param int $id The member ID
     * @return array|null Member data array or null if not found
     */
    public function findById(int $id): ?array
    {
        $query = "SELECT * FROM Medlem WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retrieves a member by ID.
     *
     * @param int $id The member ID
     * @return Medlem|null The Medlem object or null if not found
     */
    public function getById(int $id): ?Medlem
    {
        $data = $this->findById($id);
        if (!$data) {
            return null;
        }

        $medlem = new Medlem();
        $this->populateMedlem($medlem, $data);
        $medlem->roller = $this->getRolesByMemberId($id);
        return $medlem;
    }

    /**
     * Creates a new empty Medlem object for data entry.
     *
     * @return Medlem New Medlem object
     */
    public function createNew(): Medlem
    {
        return new Medlem();
    }

    /**
     * Inserts a new member.
     *
     * @param array $data Member data
     * @return int The new member ID
     */
    public function insert(array $data): int
    {
        $params = [
            "fodelsedatum", "fornamn", "efternamn", "email", "gatuadress",
            "postnummer", "postort", "mobil", "telefon", "kommentar",
            "godkant_gdpr", "pref_kommunikation", "isAdmin", "foretag",
            "standig_medlem", "skickat_valkomstbrev"
        ];

        $sql = "INSERT INTO Medlem (" . implode(', ', $params) . ") VALUES (" .
               implode(', ', array_map(fn($p) => ":$p", $params)) . ")";

        $stmt = $this->conn->prepare($sql);
        $this->bindMemberParams($stmt, $data);
        $stmt->execute();

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Updates an existing member.
     *
     * @param int $id Member ID
     * @param array $data Member data
     * @return bool Success status
     */
    public function update(int $id, array $data): bool
    {
        $params = [
            "fodelsedatum", "fornamn", "efternamn", "email", "gatuadress",
            "postnummer", "postort", "mobil", "telefon", "kommentar",
            "godkant_gdpr", "pref_kommunikation", "isAdmin", "foretag",
            "standig_medlem", "skickat_valkomstbrev"
        ];

        $sql = "UPDATE Medlem SET " . implode(', ', array_map(fn($p) => "$p = :$p", $params)) .
               " WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $this->bindMemberParams($stmt, $data);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return true;
    }

    /**
     * Saves a member (create or update).
     *
     * @param Medlem $medlem The member to save
     * @return bool Success status
     */
    public function save(Medlem $medlem): bool
    {
        try {
            $data = $this->medlemToArray($medlem);

            if (isset($medlem->id) && $medlem->id > 0) {
                $this->update($medlem->id, $data);
            } else {
                $medlem->id = $this->insert($data);
            }

            $this->saveRolesForMember($medlem->id, $medlem->roller);
            return true;
        } catch (Exception $e) {
            $this->logger->error('Failed to save medlem: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a member by ID.
     *
     * @param int $id Member ID
     * @return bool Success status
     */
    public function deleteById(int $id): bool
    {
        try {
            $this->conn->beginTransaction();

            // Remove roles first
            $stmt = $this->conn->prepare('DELETE FROM Medlem_Roll WHERE medlem_id = ?');
            $stmt->execute([$id]);

            // Remove member
            $stmt = $this->conn->prepare('DELETE FROM Medlem WHERE id = ?');
            $stmt->execute([$id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            $this->logger->error('Failed to delete medlem: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a member.
     *
     * @param Medlem $medlem The member to delete
     * @return bool Success status
     */
    public function delete(Medlem $medlem): bool
    {
        return $this->deleteById($medlem->id);
    }

    /**
     * Gets roles for a member.
     *
     * @param int $memberId Member ID
     * @return array Array of roles
     */
    public function getRolesByMemberId(int $memberId): array
    {
        $query = "SELECT mr.roll_id, r.roll_namn 
                  FROM Medlem_Roll mr
                  INNER JOIN Roll r ON mr.roll_id = r.id
                  WHERE mr.medlem_id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $memberId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Saves roles for a member.
     *
     * @param int $memberId Member ID
     * @param array $roles Array of roles
     * @return void
     */
    public function saveRolesForMember(int $memberId, array $roles): void
    {
        try {
            $this->conn->beginTransaction();

            // Get current roles
            $stmt = $this->conn->prepare("SELECT roll_id FROM Medlem_Roll WHERE medlem_id = :medlem_id");
            $stmt->execute(['medlem_id' => $memberId]);
            $currentRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $newRoles = array_map(fn($role) => (int) $role['roll_id'], $roles);

            // Add new roles
            $rolesToAdd = array_diff($newRoles, $currentRoles);
            if (!empty($rolesToAdd)) {
                $stmt = $this->conn->prepare("INSERT INTO Medlem_Roll (medlem_id, roll_id) VALUES (:medlem_id, :roll_id)");
                foreach ($rolesToAdd as $rollId) {
                    $stmt->execute(['medlem_id' => $memberId, 'roll_id' => $rollId]);
                }
            }

            // Remove old roles
            $rolesToRemove = array_diff($currentRoles, $newRoles);
            if (!empty($rolesToRemove)) {
                $stmt = $this->conn->prepare("DELETE FROM Medlem_Roll WHERE medlem_id = :medlem_id AND roll_id = :roll_id");
                foreach ($rolesToRemove as $rollId) {
                    $stmt->execute(['medlem_id' => $memberId, 'roll_id' => $rollId]);
                }
            }

            $this->conn->commit();
        } catch (Exception $e) {
            $this->conn->rollBack();
            $this->logger->error("Error updating roles for medlem ID $memberId: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Gets seglingar for a member.
     *
     * @param int $memberId Member ID
     * @return array Array of seglingar
     */
    public function getSeglingarByMemberId(int $memberId): array
    {
        $query = 'SELECT smr.medlem_id, s.id as segling_id, r.roll_namn, s.skeppslag, s.startdatum
            FROM Segling_Medlem_Roll smr
            INNER JOIN Segling s ON s.id = smr.segling_id
            LEFT JOIN Roll r ON r.id = smr.roll_id
            WHERE smr.medlem_id = :id
            ORDER BY s.startdatum DESC
            LIMIT 10';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $memberId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function bindMemberParams($stmt, array $data): void
    {
        $stmt->bindParam(':fodelsedatum', $data['fodelsedatum'], PDO::PARAM_STR);
        $stmt->bindParam(':fornamn', $data['fornamn'], PDO::PARAM_STR);
        $stmt->bindParam(':efternamn', $data['efternamn'], PDO::PARAM_STR);
        $email = $data['email'] ?: null;
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':gatuadress', $data['adress'], PDO::PARAM_STR);
        $stmt->bindParam(':postnummer', $data['postnummer'], PDO::PARAM_STR);
        $stmt->bindParam(':postort', $data['postort'], PDO::PARAM_STR);
        $stmt->bindParam(':mobil', $data['mobil'], PDO::PARAM_STR);
        $stmt->bindParam(':telefon', $data['telefon'], PDO::PARAM_STR);
        $stmt->bindParam(':kommentar', $data['kommentar'], PDO::PARAM_STR);
        $stmt->bindParam(':godkant_gdpr', $data['godkant_gdpr'], PDO::PARAM_BOOL);
        $stmt->bindParam(':pref_kommunikation', $data['pref_kommunikation'], PDO::PARAM_BOOL);
        $stmt->bindParam(':isAdmin', $data['isAdmin'], PDO::PARAM_BOOL);
        $stmt->bindParam(':foretag', $data['foretag'], PDO::PARAM_BOOL);
        $stmt->bindParam(':standig_medlem', $data['standig_medlem'], PDO::PARAM_BOOL);
        $stmt->bindParam(':skickat_valkomstbrev', $data['skickat_valkomstbrev'], PDO::PARAM_BOOL);
    }

    private function populateMedlem(Medlem $medlem, array $data): void
    {
        $medlem->id = (int) $data['id'];
        $medlem->fodelsedatum = $data['fodelsedatum'] ?? null;
        $medlem->fornamn = $data['fornamn'] ?? null;
        $medlem->efternamn = $data['efternamn'];
        $medlem->email = $data['email'] ?? null;
        $medlem->mobil = $data['mobil'] ?? null;
        $medlem->telefon = $data['telefon'] ?? null;
        $medlem->adress = $data['gatuadress'] ?? null;
        $medlem->postnummer = $data['postnummer'] ?? null;
        $medlem->postort = $data['postort'] ?? null;
        $medlem->kommentar = $data['kommentar'] ?? null;
        $medlem->godkant_gdpr = (bool) $data['godkant_gdpr'];
        $medlem->pref_kommunikation = (bool) $data['pref_kommunikation'];
        $medlem->foretag = (bool) $data['foretag'];
        $medlem->standig_medlem = (bool) $data['standig_medlem'];
        $medlem->skickat_valkomstbrev = (bool) $data['skickat_valkomstbrev'];
        $medlem->isAdmin = (bool) $data['isAdmin'];
        $medlem->password = $data['password'] ?? null;
        $medlem->created_at = $data['created_at'];
        $medlem->updated_at = $data['updated_at'];
    }

    private function medlemToArray(Medlem $medlem): array
    {
        return [
            'fodelsedatum' => $medlem->fodelsedatum,
            'fornamn' => $medlem->fornamn,
            'efternamn' => $medlem->efternamn,
            'email' => $medlem->email,
            'adress' => $medlem->adress,
            'postnummer' => $medlem->postnummer,
            'postort' => $medlem->postort,
            'mobil' => $medlem->mobil,
            'telefon' => $medlem->telefon,
            'kommentar' => $medlem->kommentar,
            'godkant_gdpr' => $medlem->godkant_gdpr,
            'pref_kommunikation' => $medlem->pref_kommunikation,
            'isAdmin' => $medlem->isAdmin,
            'foretag' => $medlem->foretag,
            'standig_medlem' => $medlem->standig_medlem,
            'skickat_valkomstbrev' => $medlem->skickat_valkomstbrev
        ];
    }

    protected function createMedlem(int $id): Medlem
    {
        return $this->getById($id);
    }
}
