<?php
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

    public function __construct($db, $id = null)
    {
        $this->conn = $db;

        if (isset($id)) {
            $this->id = $id;
            $this->getOne($this->id);
        }
    }

    public function getAll()
    {
        $seglingar = [];
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY startdatum ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();
        //Create a segling object for each row in the database, this also fetches deltagare
        foreach ($result as $row) {
            $segling = new Segling($this->conn, $row['id']);
            $seglingar[] = $segling;
        }
        return $seglingar;
    }

    function getOne($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? limit 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        //if we got a result set object values
        if($row !== false) {
            $this->id = $id;
            $this->start_dat = $row['startdatum'];
            $this->slut_dat = $row['slutdatum'];
            $this->skeppslag = $row['skeppslag'];
            $this->kommentar = $row['kommentar'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];

            //Get roller from junction table
            $this->deltagare = $this->getPeople();
        }
    }

    public function getPeople()
    {
        $query = "SELECT smr.medlem_id, m.fornamn, m.efternamn, smr.roll_id, r.roll_namn
                    FROM Segling_Medlem_Roll smr
                    INNER JOIN Medlem m ON smr.medlem_id = m.id
                    INNER JOIN Roll r ON smr.roll_id = r.id
                    WHERE smr.segling_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

    public function update()
    {
        $query = "UPDATE $this->table_name SET 
        startdatum = \"$this->start_dat \", 
        slutdatum = \"$this->slut_dat\",
        skeppslag = \"$this->skeppslag\" 
        WHERE id = ?;";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
    }
    public function updatePeople()
    {
        $query = "DELETE FROM Segling_Medlem_Roll WHERE segling_id = ?; ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        foreach ($this->deltagare as $pers) {
            $query = 'INSERT INTO Segling_Medlem_Roll (segling_id, medlem_id, roll_id) VALUES (?, ?, ?);';
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            $stmt->bindParam(2, $pers["medlem_id"]);
            $stmt->bindParam(3, $pers["roll_id"]);
            $stmt->execute();
        }
    }
    public function delete()
    {
        $query = "DELETE FROM Segling WHERE segling_id = ?; ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
    }
    public function create()
    {
        $query = 'INSERT INTO Segling (startdatum, slutdatum, skeppslag) VALUES (?, ?, ?);';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->start_dat);
        $stmt->bindParam(2, $this->slut_dat);
        $stmt->bindParam(3, $this->skeppslag);
        $stmt->execute();

        $this->id = $this->conn->lastInsertId();
        $this->updatePeople();
        $this->getOne($this->id);
    }

    public function getDeltagareByRoleName($targetRole)
    {
        $results = [];

        // Loop through each inner array and fetch fornamn, efternamn for matching persons
        foreach ($this->deltagare as $crewMember) {
            if ($crewMember['roll_namn'] === $targetRole) {
                $newDeltagare = [
                    'fornamn' => $crewMember['fornamn'],
                    'efternamn' => $crewMember['efternamn']
                ];
                $results[] = $newDeltagare;
            }
        }
        return $results;
    }
}
