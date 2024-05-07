<?php

require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/medlem.php';
require_once __DIR__ . '/../models/Roll.php';

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
        //Fetch member data
        $medlem = new Medlem($this->conn, $id);
        $roll = new Roll($this->conn);
        //Fetch roles to populate checkboxes
        $roller = $roll->getAll();
        $data = array(
            "title" => "Visa medlem",
            "items" => $medlem,
            "roles" => $roller
          );
        require __DIR__ . '/../views/viewMedlemEdit.php';
    }

    public function save(array $params) {
        $id = $params['id'];
        $medlem = new Medlem($this->conn, $id);

        foreach ($_POST as $key => $value) {
          //Special handling for roller that is an array of ids
          if($key === 'roller') {
            $medlem->updateMedlemRoles($value);
          }
          elseif (property_exists($medlem, $key)) {
            $_POST[$key] = $this->sanitizeInput($value);
            $medlem->$key = $value; // Assign value to corresponding property
          }
        }
        $medlem->save();
        //TODO add error handling
        $_SESSION['flash_message'] = array('type'=>'ok', 'message'=>'Medlem uppdaterad!');

        // Set the URL and redirect
        $redirectUrl = $this->router->generate('medlem-list');
        header('Location: ' . $redirectUrl);
        exit;
    }

}