<?php

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../models/medlem.php';

class MedlemController{  

    private $conn;
    private $request;

    public function __construct($request) 
    {
        $this->request = $request;
        $this->conn = $this->getDatabaseConn();
    }

    public function list(){
        require __DIR__ . '/../views/viewMedlem.php';
    }

    public function edit(array $params){
        require __DIR__ . '/../views/viewMedlemEdit.php';
    }

    private function getDatabaseConn() {
        // get database connection
        $database = new Database();
        return $database->getConnection();
    }

}