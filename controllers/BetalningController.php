<?php

require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Betalning.php';
require_once __DIR__ . '/../models/Medlem.php';

class BetalningController extends BaseController {  

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

    public function getBetalning(array $params) {
        $id = $params['id'];
        $betalning = new Betalning($this->conn);
        $betalning->getOne($id);
        var_dump($betalning);
    }
}