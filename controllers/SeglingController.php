<?php

require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Segling.php';
require_once __DIR__ . '/../models/Roll.php';
require_once __DIR__ . '/../models/MedlemRepository.php';

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
        //Fetch all available roles
        $roll = new Roll($this->conn);
        $roller = $roll->getAll();
 
        //Fetch lists of persons who has a role to populate select boxes
        $medlemmar = new MedlemRepository($this->conn);
        $allaSkeppare = $medlemmar->getMembersByRollName('Skeppare');
        $allaBatsman = $medlemmar->getMembersByRollName('Båtsman');
        $allaKockar = $medlemmar->getMembersByRollName('Kock');

        $data = array(
            "title" => "Visa segling",
            "items" => $segling,
            "roles" => $roller,
            "allaSkeppare" => $allaSkeppare,
            "allaBatsman" => $allaBatsman,
            "allaKockar" => $allaKockar
          );
        require __DIR__ . '/../views/viewSeglingEdit.php';
    }

    public function save(array $params) {
        $id = $params['id'];
        $segling = new Segling($this->conn, $id);
        var_dump($_POST);
        exit;
        
        //TODO add logic to save
        $segling->start_dat = $this->sanitizeInput($_POST['startdat']);
        $segling->start_dat = $this->sanitizeInput($_POST['slutdat']);
        $segling->skeppslag = $this->sanitizeInput($_POST['skeppslag']);
        if(isset($_POST['kommentar'])) {
            $segling->kommentar = $this->sanitizeInput($_POST['kommentar']);
        }
        $segling->save();
        
        //TODO add error handling
        $_SESSION['flash_message'] = array('type'=>'ok', 'message'=>'Segling uppdaterad!');
        
        // Set the URL and redirect
        $redirectUrl = $this->router->generate('segling-list');
        header('Location: ' . $redirectUrl);
        exit;
    }

}