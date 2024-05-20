<?php

require_once __DIR__ . '/../models/Medlem.php';
require_once __DIR__ . '/../config/database.php';

class MedlemRepository
{
    // database connection and table name
    private $conn;
    public $medlemmar;


    public function __construct()
    {
        $this->conn = $this->getDatabaseConn();
    }

    public function getAll()
    {
        $query = "SELECT id from Medlem";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getMembersByRollName($rollId)
    {
        // Implement logic to query Medlem, Roll, and Medlem_Roll tables
        // to find members with the specified roll ID
        // ...
    }

    private function getDatabaseConn()
    {
        // get database connection
        $database = new Database();
        return $database->getConnection();
    }
}
