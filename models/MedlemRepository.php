<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Medlem.php';

class MedlemRepository
{
    // database connection and table name
    private $conn;
    public $medlemmar;

    public function __construct($db)
    {
        $this->conn = $db;
    }


    // Fetches all members by querying Medlem table in DB
    // Return: array of Medlem objects
    public function getAll()
    {
        $medlemmar = [];

        $query = "SELECT id from Medlem";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $members =  $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($members as $member) {
            $medlem = new Medlem($this->conn, $member['id']);
            $medlemmar[] = $medlem;
        }
        return $medlemmar;
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
    //NOT USED?
    //Returns an array with member data and roles
    //Use getAll() instead as it returns proper member objects including roles.. 
    public function getAllWithRoles()
    {
        $query = "SELECT m.*, GROUP_CONCAT(r.roll_namn, ', ') AS roller
            FROM Medlem m
            INNER JOIN Medlem_Roll mr ON m.id = mr.medlem_id
            INNER JOIN Roll r ON mr.roll_id = r.id
            GROUP BY m.id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
