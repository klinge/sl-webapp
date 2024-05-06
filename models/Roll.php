<?php
class Roll{
  
    // database connection and table name
    private $conn;
    private $table_name = "Roll";
  
    // object properties
    public int $id;
    public string $roll_namn;
    public string $kommentar;
    public string $created_at;
    public string $updated_at;


    
    public function __construct($db,){
        $this->conn = $db;
    }
  
    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare( $query );
        $stmt->execute();

        return $stmt->fetchAll();
    }
}