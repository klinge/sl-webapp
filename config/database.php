<?php
class Database{
   
    // specify your own database credentials
    private $dbfile = "/var/www/html/sl-webapp/db/sldb.sqlite";
    public $conn;
   
    // get the database connection
    public function getConnection(){
   
        $this->conn = null;
   
        try{
            $this->conn = new PDO("sqlite:" . $this->dbfile);
            return $this->conn;
        }
        catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
        }
    }
}
?>