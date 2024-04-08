<?php

include_once 'models/medlem.php';

class MedlemRepository{
  
    // database connection and table name
    private $conn;
    private $table_name = "Medlem";
  
    // An array of Member objects
    protected $members = [];

    public function __construct($db){
        $this->conn = $db;
    }
  
    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare( $query );
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($result as $row) {
            $member = new Medlem($this->conn, $row['id']);
            array_push($this->members, $member);
        }
        return $this->members;
    }

    public function getAllJson() {
        $this->getAll();
        return json_encode($this->members);
    }
}