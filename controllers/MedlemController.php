<?php

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/BaseController.php';
require __DIR__ . '/../models/medlem.php';

class MedlemController extends BaseController {  

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
        var_dump($_POST);
        exit;
    }

}