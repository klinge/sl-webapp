<?php

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/BaseController.php';
require __DIR__ . '/../models/medlem.php';
require __DIR__ . '/../models/Roll.php';

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
        var_dump($_POST);
        exit;
        foreach ($_POST as $key => $value) {
          $_POST[$key] = $this->sanitizeInput($value);
          if (property_exists($medlem, $key)) {
            $medlem->$key = $value; // Assign value to corresponding property
          }
        }
        $medlem->update();
        //TODO add error handling
        $_SESSION['flash_message'] = array('type'=>'ok', 'message'=>'Medlem uppdaterad!');

        // Set the URL and redirect
        $redirectUrl = $this->router->generate('medlem-lista');
        header('Location: ' . $redirectUrl);
        exit;
    }

}