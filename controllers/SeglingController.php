<?php

require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Segling.php';
require_once __DIR__ . '/../models/Roll.php';

class SeglingController extends BaseController {  

    public function list(){
        $segling = new Segling($this->conn);
        $result = $segling->getAll();

        //Put everyting in the data variable that is used by the view
        $data = array(
            "title" => "Bokningslista",
            "items" => $result
          );
        require __DIR__ . '/../views/viewSegling.php';
    }

    public function edit(array $params){
        $id = $params['id'];
        $formAction = $this->router->generate('segling-save', ['id' => $id]);
        //Fetch member data
        $segling = new Segling($this->conn, $id);
        
        //Check if segling exists otherwise throw a 404
        if(!isset($segling->id)) {
            header("HTTP/1.1 404 Not Found");
            exit();
        } 
        $roll = new Roll($this->conn);
        //Fetch all available roles
        $roller = $roll->getAll();
        $data = array(
            "title" => "Visa segling",
            "items" => $segling,
            "roles" => $roller
          );
        require __DIR__ . '/../views/viewSeglingEdit.php';
    }

    public function save(array $params) {
        $id = $params['id'];
        $segling = new Segling($this->conn, $id);
        
        //TODO add logic to save
        var_dump($_POST);
        exit;
        
        //TODO add error handling
        $_SESSION['flash_message'] = array('type'=>'ok', 'message'=>'Medlem uppdaterad!');

        exit;
    }

}