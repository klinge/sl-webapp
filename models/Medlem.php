<?php
class Medlem
{

    // database connection and table name
    private $conn;
    private $table_name = "Medlem";

    // object properties
    public int $id;
    public string $fornamn;
    public string $efternamn;
    public string $email;
    public string $mobil;
    public string $telefon;
    public string $adress;
    public string $postnummer;
    public string $postort;
    public string $kommentar;
    public array $roller = [];
    public string $created_at;
    public string $updated_at;



    public function __construct($db, $id = null)
    {
        $this->conn = $db;

        if (isset($id)) {
            $result = $this->getDataFromDb($id);
            if ($result) 
            {
                $this->roller = $this->getRoles();
            }
            else {
                throw new Exception("Medlem med id: " . $id . "hittades inte");
            }
            
        }
    }

    private function getDataFromDb($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id limit 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        //If we got a result from db then set values for the object
        if($row !== false) 
        {
            $this->id = $id;
            $this->fornamn = $row['fornamn'];
            $this->efternamn = $row['efternamn'];
            $this->email = $row['email'];
            $this->mobil = isset($row['mobil']) ? $row['mobil'] : "";
            $this->telefon = isset($row['telefon']) ? $row['telefon'] : "";
            $this->adress = isset($row['gatuadress']) ? $row['gatuadress'] : "";
            $this->postnummer = isset($row['postnummer']) ? $row['postnummer'] : "";
            $this->postort = isset($row['postort']) ? $row['postort'] : "";
            $this->kommentar = isset($row['kommentar']) ? $row['kommentar'] : "";
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        else {
            return false;
        }
    }

    public function getNamn() 
    {
        $namn = $this->fornamn . " " . $this->efternamn;
        return $namn;
    }

    public function getRoles()
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

    function updateMedlemRoles($newRoleIds)
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
                $newRole = array('roll_id' => $roleId);
                $this->roller[] = $newRole;
            }
        }
    }

    public function save()
    {
        $query = "UPDATE $this->table_name SET 
        fornamn = :fornamn, 
        efternamn = :efternamn,
        email = :email,
        gatuadress = :gatuadress,
        postnummer = :postnummer, 
        postort = :postort, 
        mobil = :mobil, 
        telefon = :telefon, 
        kommentar = :kommentar
        WHERE id = :id;";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fornamn', $this->fornamn);
        $stmt->bindParam(':efternamn', $this->efternamn);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':gatuadress', $this->adress);
        $stmt->bindParam(':postnummer', $this->postnummer);
        $stmt->bindParam(':postort', $this->postort);
        $stmt->bindParam(':mobil', $this->mobil);
        $stmt->bindParam(':telefon', $this->telefon);
        $stmt->bindParam(':kommentar', $this->kommentar);
        $stmt->bindParam(':id', $this->id);

        $stmt->execute();

        $this->saveRoles();
    }

    public function saveRoles()
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

    public function delete()
    {
        $query = 'DELETE FROM Medlem WHERE id = ?; ';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $this->roller = [];
        $this->saveRoles();
    }

    public function create()
    {
        $query = 'INSERT INTO Medlem 
            (fornamn, efternamn, email, gatuadress, postnummer, postort, mobil, telefon, kommentar) 
            VALUES (:fornamn, :efternamn, :email, :gatuadress, :postnummer, :postort, :mobil, :telefon, :kommentar); ';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fornamn', $this->fornamn);
        $stmt->bindParam(':efternamn', $this->efternamn);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':gatuadress', $this->adress);
        $stmt->bindParam(':postnummer', $this->postnummer);
        $stmt->bindParam(':postort', $this->postort);
        $stmt->bindParam(':mobil', $this->mobil);
        $stmt->bindParam(':telefon', $this->telefon);
        $stmt->bindParam(':kommentar', $this->kommentar);

        $stmt->execute();

        $this->id = $this->conn->lastInsertId();
        $this->saveRoles();
        $this->getDataFromDb($this->id);
    }

    //Method to check if a member has a given role
    function hasRole($searchRole)
    {
        foreach ($this->roller as $role) {
            if (isset($role['roll_id']) && $role['roll_id'] === $searchRole) {
                return true;
            }
        }
        return false;
    }
    //find Seglingar a Medlem has participated in.. 
    function getSeglingar() 
    {
        $query = 'SELECT smr.medlem_id, r.roll_namn, s.skeppslag, s.startdatum
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
}
