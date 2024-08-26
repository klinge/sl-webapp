<?php

require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Betalning.php';
require_once __DIR__ . '/../models/BetalningRepository.php';

class BetalningController extends BaseController
{

    public function list()
    {
        $betalningar = new BetalningRepository($this->conn);
        $result = $betalningar->getAll();

        //Put everyting in the data variable that is used by the view
        $data = array(
            "title" => "Betalningslista",
            "items" => $result
        );
        require __DIR__ . '/../views/viewBetalning.php';
    }

    public function getBetalning(array $params)
    {
        $id = $params['id'];
        $betalning = new Betalning($this->conn);
        $betalning->get($id);
        var_dump($betalning);
    }

    public function getMedlemBetalning(array $params)
    {
        $id = $params['id'];
        $medlem = new Medlem($this->conn, $id);
        $namn = $medlem->getNamn();
        $repo = new BetalningRepository($this->conn);
        $result = $repo->getBetalningForMedlem($id);

        if (!empty($result)) {
            $data = array(
                "success" => true,
                "title" => "Betalningar för: " . $namn ,
                "items" => $result
              );
        } else {
            $data = array(
                "success" => false,
                "title" => "Inga betalningar hittades"
              );
        }

        require __DIR__ . '/../views/viewBetalning.php';
    }

    public function createBetalning(array $params)
    {
        $betalning = new Betalning($this->conn);
        
        //Check for mandatory fields
        if (empty($_POST['belopp']) || empty($_POST['datum']) || empty($_POST['avser_ar'])) {
            // Handle missing values, e.g., return an error message or redirect to the form
            $this->jsonResponse(['success' => false, 'message' => 'Belopp, datum, and avser_ar are required fields.']); 
        }

        // Validate and sanitize input
        $betalning->medlem_id = filter_input(INPUT_POST, 'medlem_id', FILTER_VALIDATE_INT);
        $betalning->datum = $this->validateDate($_POST['datum'] ?? '');
        $betalning->belopp = filter_input(INPUT_POST, 'belopp', FILTER_VALIDATE_FLOAT);
        $betalning->avser_ar = filter_input(INPUT_POST, 'avser_ar', FILTER_VALIDATE_INT);
        if (isset($_POST['kommentar'])) {
            $betalning->kommentar = $this->sanitizeInput($_POST['kommentar']);
        }
        else {
            $betalning->kommentar = "";
        }

        $input_ok = $betalning->medlem_id && $betalning->datum && $betalning->belopp && $betalning->avser_ar;

        if(!$input_ok) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid input']);
        }

        // Create the betalning
        try {
            $result = $betalning->create();
            $this->jsonResponse(['success' => true, 'message' => 'Betalning created successfully. Id of betalning: ' . $result['id']]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error creating Betalning: ' . $e->getMessage()]);
        }
    }

    public function deleteBetalning(array $params)
    {
        $id = $params['id'];
        $betalning = new Betalning($this->conn);
        $betalning->get($id);
        try{
            $betalning->delete();
        }
        catch(Exception $e)
        {

        }

    }
}
