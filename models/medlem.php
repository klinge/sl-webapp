<?php
class Medlem{
  
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


    
    public function __construct($db, $id = null){
        $this->conn = $db;
        
        if( isset($id) ) {
            $this->id = $id;
            $this->get($this->id);
        }
    }
  
    public function getAll($from_record_num, $records_per_page) {
        $query = "SELECT
                *
            FROM
                " . $this->table_name . "
            ORDER BY
                efternamn ASC
            LIMIT
                {$from_record_num}, {$records_per_page}";

        $stmt = $this->conn->prepare( $query );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function get($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? limit 0,1";
  
        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
      
        $this->id = $id;
        $this->fornamn = $row['fornamn'];
        $this->efternamn = $row['efternamn'];
        $this->email = $row['email'];
        $this->mobil = isset($row['mobil']) ? $row['mobil'] : ""; 
        $this->telefon = isset($row['telefon']) ? $row['telefon'] : "";
        $this->adress = isset($row['adress']) ? $row['adress'] : ""; 
        $this->postnummer = isset($row['postnummer']) ? $row['postnummer'] : ""; 
        $this->postort = isset($row['postort']) ? $row['postort'] : "";
        $this->kommentar = isset($row['kommentar']) ? $row['kommentar'] : "";
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];

        //Get roller from junction table
        $this->roller = $this->fetchRoles();
    }

    public function getJson($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? limit 0,1";
        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $id);
        
        try {
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($results);
        }
        catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function fetchRoles() {
        $query = "SELECT mr.roll_id, r.roll_namn 
                    FROM Medlem_Roll mr
                    INNER JOIN Roll r ON mr.roll_id = r.id
                    WHERE mr.medlem_id = ?";
        
        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }
}
?>