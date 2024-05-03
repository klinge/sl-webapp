<?php

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../models/medlem.php';

class MedlemController{  

    private $conn;
    private $request;
    private $router;

    public function __construct($request, $router) 
    {
        $this->request = $request;
        $this->conn = $this->getDatabaseConn();
        $this->router = $router;
    }

    public function list(){
        $medlem = new Medlem($this->conn);
        $result = $medlem->getAllWithRoles();
        //Put everyting in the data variable that is used by the view
        $data = array(
            "title" => "Besättningslista",
            "items" => $result
          );
        require __DIR__ . '/../views/viewMedlem.php';
    }

    public function edit(array $params){
        $id = $params['id'];
        $formAction = $this->router->generate('medlem-save', ['id' => $id]);
        $medlem = new Medlem($this->conn, $id);
        $data = array(
            "title" => "Visa medlem",
            "items" => $medlem
          );
        require __DIR__ . '/../views/viewMedlemEdit.php';
    }

    public function save(array $params) {
        $id = $params['id'];
        echo "In member#save";
        die;
    }

    private function getDatabaseConn() {
        // get database connection
        $database = new Database();
        return $database->getConnection();
    }

}