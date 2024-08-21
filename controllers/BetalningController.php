<?php

require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Betalning.php';
require_once __DIR__ . '/../models/BetalningRepository.php';

class BetalningController extends BaseController {  

    public function list(){
        $betalningar = new BetalningRepository($this->conn);
        $result = $betalningar->getAll();

        //Put everyting in the data variable that is used by the view
        $data = array(
            "title" => "Betalningslista",
            "items" => $result
          );
        require __DIR__ . '/../views/viewBetalning.php';
    }

    public function getBetalning(array $params) {
        $id = $params['id'];
        $betalning = new Betalning($this->conn);
        $betalning->get($id);
        var_dump($betalning);
    }

    public function getMedlemBetalning(array $params) {
        $id = $params['id'];
        $repo = new BetalningRepository($this->conn);
        $result = $repo->getBetalningForMedlem($id);
        if(!empty($result))
        {
            var_dump($result);
        }
        else 
        {
            echo "Inga betalningar hittades";
        }

    }
}