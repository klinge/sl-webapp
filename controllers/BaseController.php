<?php 

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/database.php';

class BaseController{  

    protected $conn;
    protected $request;
    protected $router;

    public function __construct($request, $router) 
    {
        $this->request = $request;
        $this->conn = $this->getDatabaseConn();
        $this->router = $router;
    }

    private function getDatabaseConn() {
        // get database connection
        $database = new Database();
        return $database->getConnection();
    }

}