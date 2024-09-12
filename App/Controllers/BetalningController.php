<?php

namespace App\Controllers;

use Exception;
use App\Models\Betalning;
use App\Models\BetalningRepository;
use App\Models\Medlem;
use App\Utils\Sanitizer;

class BetalningController extends BaseController
{
    public function list()
    {
        $isLoggedIn = $this->requireLogin();
        if ($isLoggedIn) {
            $betalningar = new BetalningRepository($this->conn);
            $result = $betalningar->getAll();

            //Put everyting in the data variable that is used by the view
            $data = [
                "title" => "Betalningslista",
                "items" => $result
            ];
            $this->render('viewBetalning', $data);
        }
    }

    public function getBetalning(array $params)
    {
        $id = $params['id'];
        $betalning = new Betalning($this->conn);
        $betalning->get($id);
        var_dump($betalning);
        //TODO add a view or modal to edit a payment..
        return $betalning;
    }

    public function getMedlemBetalning(array $params)
    {
        $id = $params['id'];
        $medlem = new Medlem($this->conn, $id);
        $namn = $medlem->getNamn();
        $repo = new BetalningRepository($this->conn);
        $result = $repo->getBetalningForMedlem($id);

        if (!empty($result)) {
            $data = [
                "success" => true,
                "title" => "Betalningar för: " . $namn,
                "items" => $result
            ];
        } else {
            $data = [
                "success" => false,
                "title" => "Inga betalningar hittades"
            ];
        }
        $this->render('viewBetalning', $data);
    }

    public function createBetalning(array $params)
    {
        $betalning = new Betalning($this->conn);

        //Check for mandatory fields
        if (empty($_POST['belopp']) || empty($_POST['datum']) || empty($_POST['avser_ar'])) {
            // Handle missing values, e.g., return an error message or redirect to the form
            $this->jsonResponse(['success' => false, 'message' => 'Belopp, datum, and avser_ar are required fields.']);
        }

        //Sanitize user input
        $sanitizer = new Sanitizer();
        $rules = [
            'medlem_id' => 'string',
            'datum' => ['date', 'Y-m-d'],
            'avser_ar' => ['date', 'Y'],
            'belopp' => 'float',
            'kommentar' => 'string',
        ];
        $cleanValues = $sanitizer->sanitize($_POST, $rules);

        $betalning->medlem_id = $cleanValues['medlem_id'];
        $betalning->datum = $cleanValues['datum'];
        $betalning->belopp = $cleanValues['belopp'];
        $betalning->avser_ar = $cleanValues['avser_ar'];
        $betalning->kommentar = $cleanValues['kommentar'] ?: null;

        $input_ok = $betalning->medlem_id && $betalning->datum && $betalning->belopp && $betalning->avser_ar;

        if (!$input_ok) {
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
        try {
            $betalning->delete();
        } catch (Exception $e) {
            //TODO: Handle exception
        }
    }
}
