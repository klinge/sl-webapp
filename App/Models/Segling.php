<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Exception;
use PDOException;

class Segling
{
    // database connection and table name
    private $conn;
    private $table_name = "Segling";

    // object properties
    public int $id;
    public string $start_dat;
    public string $slut_dat;
    public string $skeppslag;
    public ?string $kommentar;
    public array $deltagare = [];
    public string $created_at;
    public string $updated_at;

    public function __construct(PDO $db, ?int $id = null, ?string $withdeltagare = null)
    {
        $this->conn = $db;

        if (isset($id)) {
            if ($withdeltagare == 'withdeltagare') {
                $result = $this->getSeglingWithDeltagare($id);
            } else {
                $result = $this->getSegling($id);
            }

            if (!$result) {
                throw new Exception("Segling med id: " . $id . "hittades inte");
            }
        }
    }

    protected function getSegling(int $id): bool
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? limit 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        //if we got a result set object values else return false
        if ($row !== false) {
            $this->id = (int) $id;
            $this->start_dat = $row['startdatum'];
            $this->slut_dat = $row['slutdatum'];
            $this->skeppslag = $row['skeppslag'];
            $this->kommentar = $row['kommentar'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        } else {
            return false;
        }
    }

    protected function getSeglingWithDeltagare(int $id): bool
    {
        //Fetch the Segling details and populate the object
        $result = $this->getSegling($id);
        //Get roller from junction table
        if ($result) {
            $this->deltagare = $this->getDeltagare();
            return true;
        } else {
            return false;
        }
    }

    public function save(): bool
    {
        $query = "UPDATE $this->table_name SET 
        startdatum = :startdatum, 
        slutdatum = :slutdatum, 
        skeppslag = :skeppslag, 
        kommentar = :kommentar
        WHERE id = :id;";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':startdatum', $this->start_dat, PDO::PARAM_STR);
        $stmt->bindParam(':slutdatum', $this->slut_dat, PDO::PARAM_STR);
        $stmt->bindParam(':skeppslag', $this->skeppslag, PDO::PARAM_STR);
        $stmt->bindValue(':kommentar', $this->kommentar ?: null, PDO::PARAM_STR);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        //If all is okay exactly one row should have been updated
        if ($stmt->rowCount() == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function delete(): bool
    {
        $query = "DELETE FROM Segling WHERE id = ?; ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id, PDO::PARAM_INT);
        $stmt->execute();
        //If all is okay exactly one row should have been deleted
        if ($stmt->rowCount() == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function create(): bool|int
    {
        $query = 'INSERT INTO Segling (startdatum, slutdatum, skeppslag, kommentar) VALUES (:startdat, :slutdat, :skeppslag, :kommentar);';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':startdat', $this->start_dat, PDO::PARAM_STR);
        $stmt->bindParam(':slutdat', $this->slut_dat, PDO::PARAM_STR);
        $stmt->bindParam(':skeppslag', $this->skeppslag, PDO::PARAM_STR);
        $stmt->bindValue(':kommentar', $this->kommentar ?: null, PDO::PARAM_STR);
        $stmt->execute();

        //If all is okay exactly one row should have been inserted
        if ($stmt->rowCount() == 1) {
            $this->id = $this->conn->lastInsertId();
            return $this->id;
            // You can use $lastInsertId if you need the ID of the newly inserted row
        } else {
            return false;
        }
    }

    /*
    * Functions for handling deltagare on a Segling
    */

    public function getDeltagare(): array
    {
        //Left join on Roll to get results even if a Medlem has no Roll for a Segling
        $query = "SELECT smr.medlem_id, m.fornamn, m.efternamn, smr.roll_id, r.roll_namn
                    FROM Segling_Medlem_Roll smr
                    JOIN Medlem m ON smr.medlem_id = m.id
                    LEFT JOIN Roll r ON smr.roll_id = r.id
                    WHERE smr.segling_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }
    /*
    * Lists all deltagare on a Segling with a specific Role
    * Returns an array of arrays with id, fornamn, efternamn for persons that match the given role
    *
    * @param string $targetRole
    * @return array
    */
    public function getDeltagareByRoleName(string $targetRole): array
    {
        $results = [];

        // Loop through each inner array and fetch id, fornamn, efternamn for matching persons
        foreach ($this->deltagare as $crewMember) {
            if ($crewMember['roll_namn'] === $targetRole) {
                $newDeltagare = [
                    'id' => $crewMember['medlem_id'],
                    'fornamn' => $crewMember['fornamn'],
                    'efternamn' => $crewMember['efternamn']
                ];
                $results[] = $newDeltagare;
            }
        }
        return $results;
    }
}
