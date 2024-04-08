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
    public array $roller = [];
    public string $created_at;
    public string $updated_at;
  
    public function __construct($db, $id = null){
        $this->conn = $db;
        $this->id = $id;

        if( isset($this->id) ) {
            $this->get($this->id);
        }
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

        //Get roller from junction table
        $this->roller = $this->getRoles();
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

    public function getRoles() {
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