<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use Exception;
use Monolog\Logger;
use App\Utils\Session;

class Medlem
{
    // database connection and table name
    private $conn;
    private $table_name = "Medlem";
    private $logger;

    // Class properties
    public int $id;
    public ?string $fodelsedatum;
    public ?string $fornamn;
    public string $efternamn;
    public ?string $email;
    public ?string $mobil;
    public ?string $telefon;
    public ?string $adress;
    public ?string $postnummer;
    public ?string $postort;
    public ?string $kommentar;
    // User preferences all booleans
    public bool $godkant_gdpr;
    public bool $pref_kommunikation;
    public bool $foretag;
    public bool $standig_medlem;
    public bool $skickat_valkomstbrev;
    public bool $isAdmin;
    // User login
    public ?string $password;
    //Fetched from Roller table
    public ?array $roller = [];
    // Timestamps
    public string $created_at;
    public string $updated_at;

    public function __construct(PDO $db, Logger $logger, int $id = null)
    {
        $this->conn = $db;
        $this->logger = $logger;

        if (isset($id)) {
            $result = $this->getDataFromDb($id);
            if ($result) {
                $this->roller = $this->getRoles();
            } else {
                throw new Exception("Medlem med id: " . $id . "hittades inte");
            }
        }
    }

    public function getNamn(): string
    {
        $namn = $this->fornamn . " " . $this->efternamn;
        return $namn;
    }

    public function save(): void
    {
        $this->logger->info("Medlem: " . $this->fornamn . " " . $this->efternamn . " uppdaterad av användare: " . Session::get('user_id'));

        $query = "UPDATE $this->table_name SET 
        fodelsedatum = :fodelsedatum,
        fornamn = :fornamn, 
        efternamn = :efternamn,
        email = :email,
        gatuadress = :gatuadress,
        postnummer = :postnummer, 
        postort = :postort, 
        mobil = :mobil, 
        telefon = :telefon, 
        kommentar = :kommentar,
        godkant_gdpr = :godkant_gdpr,
        pref_kommunikation = :pref_kommunikation,
        isAdmin = :isAdmin
        WHERE id = :id;";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fodelsedatum', $this->fodelsedatum);
        $stmt->bindParam(':fornamn', $this->fornamn);
        $stmt->bindParam(':efternamn', $this->efternamn);
        $email = $this->email ?: null; //Make sure empty emails is stored as null
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':gatuadress', $this->adress);
        $stmt->bindParam(':postnummer', $this->postnummer);
        $stmt->bindParam(':postort', $this->postort);
        $stmt->bindParam(':mobil', $this->mobil);
        $stmt->bindParam(':telefon', $this->telefon);
        $stmt->bindParam(':kommentar', $this->kommentar);
        $stmt->bindParam(':godkant_gdpr', $this->godkant_gdpr);
        $stmt->bindParam(':pref_kommunikation', $this->pref_kommunikation);
        $stmt->bindParam(':isAdmin', $this->isAdmin);
        $stmt->bindParam(':id', $this->id);

        $stmt->execute();

        $this->saveRoles();
    }

    public function saveUserProvidedPassword(): void
    {
        $password = password_hash($this->password, PASSWORD_DEFAULT);

        $query = 'UPDATE $this->table_name SET password = ? WHERE id = ?; ';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $password);
        $stmt->bindParam(2, $this->id);
        $stmt->execute();
    }

    public function delete(): void
    {
        $this->logger->info("Medlem: " . $this->fornamn . " " . $this->efternamn . "borttagen av användare: " . Session::get('user_id'));

        $query = 'DELETE FROM Medlem WHERE id = ?; ';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        //Also remove all roles
        $this->roller = [];
        $this->saveRoles();
    }

    public function create(): int
    {
        $query = 'INSERT INTO Medlem 
            (fodelsedatum, fornamn, efternamn, email, gatuadress, postnummer, postort, mobil, telefon, kommentar, godkant_gdpr, pref_kommunikation, isAdmin) 
            VALUES (:fodelsedatum, :fornamn, :efternamn, :email, :gatuadress, :postnummer, :postort, :mobil, :telefon, :kommentar, :godkant_gdpr, :pref_kommunikation, :isAdmin); ';
        $stmt = $this->conn->prepare($query);
        try {
            $stmt->bindParam(':fodelsedatum', $this->fodelsedatum, PDO::PARAM_STR);
            $stmt->bindParam(':fornamn', $this->fornamn, PDO::PARAM_STR);
            $stmt->bindParam(':efternamn', $this->efternamn, PDO::PARAM_STR);
            //If email is empty make sure to save it as null, to avoid UNIQUE issues
            $email = $this->email ?: null;
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':gatuadress', $this->adress, PDO::PARAM_STR);
            $stmt->bindParam(':postnummer', $this->postnummer, PDO::PARAM_STR);
            $stmt->bindParam(':postort', $this->postort, PDO::PARAM_STR);
            $stmt->bindParam(':mobil', $this->mobil, PDO::PARAM_STR);
            $stmt->bindParam(':telefon', $this->telefon, PDO::PARAM_STR);
            $stmt->bindParam(':kommentar', $this->kommentar, PDO::PARAM_STR);
            $stmt->bindParam(':godkant_gdpr', $this->godkant_gdpr, PDO::PARAM_BOOL);
            $stmt->bindParam(':pref_kommunikation', $this->pref_kommunikation, PDO::PARAM_BOOL);
            $stmt->bindParam(':isAdmin', $this->isAdmin, PDO::PARAM_BOOL);

            $stmt->execute();

            $this->id = (int) $this->conn->lastInsertId();
            $this->saveRoles();
            $this->getDataFromDb($this->id);

            $this->logger->info("Medlem: " . $this->fornamn . " " . $this->efternamn . "skapad av användare: " . Session::get('user_id'));

            return $this->id;
        } catch (PDOException $e) {
            $this->logger->warning("PDOException in Medlem::create. Error: " . $e->getMessage());
            if ($e->getCode() == '23000') {
                //For troubleshooting csv import and handling trouble with null vs empty strings in email field
                $this->logger->debug("UNIQUE constraint violation for email: " . $this->email);
            }
            throw $e; // Re-throw the exception if you want to handle it further up the call stack
        }
    }


    //find Seglingar a Medlem has participated in..
    public function getSeglingar(): array
    {
        $query = 'SELECT smr.medlem_id, s.id as segling_id, r.roll_namn, s.skeppslag, s.startdatum
            FROM Segling_Medlem_Roll smr
            INNER JOIN Roll r ON r.id = smr.roll_id
            INNER JOIN Segling s ON s.id = smr.segling_id
            WHERE smr.medlem_id = :id
            ORDER BY s.startdatum DESC
            LIMIT 10;';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $results;
    }

    //
    // FUNCTIONS RELATED TO ROLES
    //
    public function getRoles(): array
    {
        $query = "SELECT mr.roll_id, r.roll_namn 
                    FROM Medlem_Roll mr
                    INNER JOIN Roll r ON mr.roll_id = r.id
                    WHERE mr.medlem_id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

    public function saveRoles(): void
    {
        $query = 'DELETE FROM Medlem_Roll WHERE medlem_id = ?; ';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        foreach ($this->roller as $roll) {
            $query = 'INSERT INTO Medlem_Roll (medlem_id, roll_id) VALUES (?, ?);';
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            $stmt->bindParam(2, $roll["roll_id"]);
            $stmt->execute();
        }
    }

    public function updateMedlemRoles($newRoleIds)
    {
        //first remove roles from that no longer exist
        $rolesToRemove = array_diff(array_column($this->roller, 'roll_id'), $newRoleIds);

        foreach ($rolesToRemove as $roleId) {
            $key = array_search($roleId, array_column($this->roller, 'roll_id'));
            unset($this->roller[$key]);
        }
        //then add new roles
        foreach ($newRoleIds as $roleId) {
            if (!in_array($roleId, array_column($this->roller, 'roll_id'))) {
                $newRole = ['roll_id' => $roleId];
                $this->roller[] = $newRole;
            }
        }
    }

    //Method to check if a member has a given role
    public function hasRole(string $searchRole): bool
    {
        $extractRollId = function ($role) {
            return $role['roll_id'] ?? null;
        };

        return in_array($searchRole, array_map($extractRollId, $this->roller));
    }

    private function getDataFromDb($id): bool
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id limit 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        //If we got a result from db then set values for the object
        if ($row !== false) {
            $this->id = (int) $id;
            $this->fodelsedatum = isset($row['fodelsedatum']) ? $row['fodelsedatum'] : "";
            $this->fornamn = $row['fornamn'];
            $this->efternamn = $row['efternamn'];
            $this->email = $row['email'];
            $this->mobil = isset($row['mobil']) ? $row['mobil'] : "";
            $this->telefon = isset($row['telefon']) ? $row['telefon'] : "";
            $this->adress = isset($row['gatuadress']) ? $row['gatuadress'] : "";
            $this->postnummer = isset($row['postnummer']) ? $row['postnummer'] : "";
            $this->postort = isset($row['postort']) ? $row['postort'] : "";
            $this->kommentar = isset($row['kommentar']) ? $row['kommentar'] : "";
            //All bools have default values in the db so can never be empty
            $this->godkant_gdpr = $row['godkant_gdpr'];
            $this->pref_kommunikation = $row['pref_kommunikation'];
            $this->foretag = $row['foretag'];
            $this->standig_medlem = $row['standig_medlem'];
            $this->skickat_valkomstbrev = $row['skickat_valkomstbrev'];
            $this->isAdmin = $row['isAdmin'];
            //End bools
            $this->password = isset($row['password']) ? $row['password'] : "";
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        } else {
            return false;
        }
    }
}
