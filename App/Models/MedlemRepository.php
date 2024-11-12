<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Exception;
use App\Application;
use Monolog\Logger;

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

        $query = "SELECT id FROM Medlem ORDER BY efternamn ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $members =  $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($members as $member) {
            try {
                $medlem = $this->createMedlem($this->conn, $this->app->getLogger(), $member['id']);
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function createMedlem(PDO $conn, Logger $logger, int $id): Medlem
    {
        return new Medlem($conn, $logger, $id);
    }
}
