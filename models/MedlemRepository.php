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

    // Query Medlem, Roll, and Medlem_Roll tables
    // to find members with a specified roll_namn
    public function getMembersByRollName($rollName)
    {
        $query = "SELECT m.id,m.fornamn, m.efternamn, r.roll_namn
            FROM  Medlem m
            INNER JOIN Medlem_Roll mr ON mr.medlem_id = m.id
            INNER JOIN Roll r ON r.id = mr.roll_id
            WHERE r.roll_namn = :rollnamn
            ORDER BY m.efternamn ASC;";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rollnamn', $rollName);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDatabaseConn()
    {
        // get database connection
        $database = new Database();
        return $database->getConnection();
    }
}
