<?php
class Segling{

     // database connection and table name
     private $conn;
     private $table_name = "Segling";
   
     // object properties
     public int $id;
     public string $start_dat;
     public string $slut_dat;
     public string $skeppslag;
     public array $deltagare = [];
     public string $created_at;
     public string $updated_at;

    public function __construct($db, $id = null){
        $this->conn = $db;
        $this->id = $id;

        if( isset($this->id) ) {
            $this->get($this->id);
        }
    }
    function get($id){
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? limit 0,1";
  
        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
      
        $this->id = $id;
        $this->start_dat = $row['startdatum'];
        $this->slut_dat = $row['slutdatum'];
        $this->skeppslag = $row['skeppslag'];

        //Get roller from junction table
        $this->deltagare = $this->getPeople();
    }

    public function getPeople() {
        $query = "SELECT smr.medlem_id, m.fornamn, m.efternamn, smr.roll_id, r.roll_namn
                    FROM Segling_Medlem_Roll smr
                    INNER JOIN Medlem m ON smr.medlem_id = m.id
                    INNER JOIN Roll r ON smr.roll_id = r.id
                    WHERE smr.segling_id = ?";
                    
        $stmt = $this->conn->prepare( $query );
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

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
    }
}

?>