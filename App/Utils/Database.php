<?php

namespace App\Utils;

use PDO;
use PDOException;

class Database{
   
    // specify your own database credentials
    private $dbfile = "/var/www/html/sl-webapp/db/sldb.sqlite";
    public $conn;
   
    // get the database connection
    public function getConnection(){
   
        $this->conn = null;
   
        try{
            $this->conn = new PDO("sqlite:" . $this->dbfile);
            
            // Enable foreign key constraints
            $this->conn->exec("PRAGMA foreign_keys = ON;");

            // Set attributes for error handling, exceptions and fetch mode
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $this->conn;
        }
        catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
        }
    }
}
?>