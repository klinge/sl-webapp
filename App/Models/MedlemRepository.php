<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Exception;
use App\Application;

class MedlemRepository
{
    // database connection and table name
    private $conn;
    private $app;
    public $medlemmar;

    public function __construct(PDO $db, Application $app)
    {
        $this->conn = $db;
        $this->app = $app;
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

        $query = "SELECT id from Medlem ORDER BY efternamn ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $members =  $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($members as $member) {
            try {
                $medlem = new Medlem($this->conn, $this->app->getLogger(), $member['id']);
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
    //NOT USED?
    //Returns an array with member data and roles
    //Use getAll() instead as it returns proper member objects including roles..
    public function getAllWithRoles(): array
    {
        $query = "SELECT m.*, GROUP_CONCAT(r.roll_namn, ', ') AS roller
            FROM Medlem m
            INNER JOIN Medlem_Roll mr ON m.id = mr.medlem_id
            INNER JOIN Roll r ON mr.roll_id = r.id
            GROUP BY m.id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
