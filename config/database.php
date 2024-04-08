<?php
class Database{
   
    // specify your own database credentials
    private $dbfile = "./db/sldb.sqlite";
    public $conn;
   
    // get the database connection
    public function getConnection(){
   
        $this->conn = null;
   
        try{
            $this->conn = new PDO("sqlite:" . $this->dbfile);
        }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
        }
   
        return $this->conn;
    }
}
?>