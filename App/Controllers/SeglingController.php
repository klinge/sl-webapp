<?php

namespace App\Controllers;

use App\Models\BetalningRepository;
use App\Models\Segling;
use App\Models\SeglingRepository;
use App\Models\MedlemRepository;
use App\Models\Roll;
use App\Utils\Session;
use Exception;
use PDO;
use PDOException;

class SeglingController extends BaseController
{
    public function list()
    {
        $isLoggedIn = $this->requireLogin();
        if ($isLoggedIn) {
            $seglingar = new SeglingRepository($this->conn);
            $result = $seglingar->getAllWithDeltagare();

            //Put everyting in the data variable that is used by the view
            $data = [
                "title" => "Bokningslista",
                "items" => $result
            ];
            $this->render('viewSegling', $data);
        }
    }

    public function edit(array $params)
    {
        $id = $params['id'];
        $formAction = $this->router->generate('segling-save', ['id' => $id]);
        //Fetch Segling
        try {
            $segling = new Segling($this->conn, $id);
        } catch (Exception $e) {
            header("HTTP/1.1 404 Not Found");
            exit();
        }
        //Get all deltagare for this segling
        $segling->deltagare = $segling->getDeltagare();

        //Fetch payment status for deltagare and add to the $deltagare array
        $year = substr($segling->start_dat, 0, 4);
        $deltagareWithBetalning = [];
        $betalningsRepo = new BetalningRepository($this->conn);

        foreach ($segling->deltagare as $deltagare) {
            $hasPayed = $betalningsRepo->memberHasPayed($deltagare['medlem_id'], $year);
            $deltagare['har_betalt'] = $hasPayed;
            $deltagareWithBetalning[] = $deltagare;
        }

        //Save the deltagare and betalning info in the $segling object
        $segling->deltagare = $deltagareWithBetalning;

        //Fetch all available roles
        $roll = new Roll($this->conn);
        $roller = $roll->getAll();

        //Fetch lists of persons who has a role to populate select boxes
        $medlemmar = new MedlemRepository($this->conn);
        $allaSkeppare = $medlemmar->getMembersByRollName('Skeppare');
        $allaBatsman = $medlemmar->getMembersByRollName('Båtsman');
        $allaKockar = $medlemmar->getMembersByRollName('Kock');

        $data = [
            "title" => "Visa segling",
            "items" => $segling,
            "roles" => $roller,
            "allaSkeppare" => $allaSkeppare,
            "allaBatsman" => $allaBatsman,
            "allaKockar" => $allaKockar,
            "formUrl" => $formAction
        ];
        $this->render('viewSeglingEdit', $data);
    }

    public function save(array $params)
    {
        $id = $params['id'];
        $segling = new Segling($this->conn, $id);

        //TODO add logic to save
        $segling->start_dat = $this->sanitizeInput($_POST['startdat']);
        $segling->slut_dat = $this->sanitizeInput($_POST['slutdat']);
        $segling->skeppslag = $this->sanitizeInput($_POST['skeppslag']);
        if (isset($_POST['kommentar'])) {
            $segling->kommentar = $this->sanitizeInput($_POST['kommentar']);
        }
        if ($segling->save()) {
            // Set the URL and redirect
            Session::setFlashMessage('success', 'Segling uppdaterad!');
            $redirectUrl = $this->router->generate('segling-list');
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            $return = ['success' => false, 'message' => 'Kunde inte uppdatera seglingen. Försök igen.'];
            $this->jsonResponse($return);
            exit;
        }
    }

    public function delete(array $params)
    {
        $id = $params['id'];
        $segling = new Segling($this->conn, $id);
        if ($segling->delete()) {
            Session::setFlashMessage('success', 'Seglingen är nu borttagen!');
            exit;
        } else {
            Session::setFlashMessage('error', 'Kunde inte ta bort seglingen. Försök igen.');
            exit;
        }
    }
    //
    //FUNCTIONS THAT HANDLES MEMBERS ON A Segling
    //
    public function saveMedlem()
    {
        //validate input
        if (!isset($_POST['segling_id']) || !isset($_POST['segling_person'])) {
            $return = ['success' => false, 'message' => "Missing input"];
            $this->jsonResponse($return);
            exit;
        }
        //check if Medlem is already on the segling
        $query = "SELECT * FROM Segling_Medlem_Roll WHERE segling_id = :segling_id AND medlem_id = :medlem_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segling_id', $_POST['segling_id'], PDO::PARAM_INT);
        $stmt->bindParam(':medlem_id', $_POST['segling_person'], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //if we got a result then the Medlem is already added to the Segling so return an error
        if (count($result) > 0) {
            $return = ['success' => false, 'message' => "Medlemmen är redan tillagd på seglingen."];
            $this->jsonResponse($return);
            exit;
        }

        //If not, insert the row, don't forget if roll was set or not..
        //First check if the role field was set or not, value 999 means that no role was selected
        $hasRole = ($_POST['segling_roll'] == 999) ? false : true;
        if ($hasRole) {
            $query = "INSERT INTO Segling_Medlem_Roll (segling_id, medlem_id, roll_id) VALUES (:segling_id, :medlem_id, :roll_id)";
        } else {
            $query = "INSERT INTO Segling_Medlem_Roll (segling_id, medlem_id) VALUES (:segling_id, :medlem_id)";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segling_id', $_POST['segling_id'], PDO::PARAM_INT);
        $stmt->bindParam(':medlem_id', $_POST['segling_person'], PDO::PARAM_INT);
        if ($hasRole) {
            $stmt->bindParam(':roll_id', $_POST['segling_roll'], PDO::PARAM_INT);
        }
        //Then try to update anc catch PDO exceptions..
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            $return = ['success' => false, 'message' => "PDO error: " + $e->getMessage()];
            $this->jsonResponse($return);
            exit;
        }

        if ($stmt->rowCount() == 1) {
            $return = ['success' => true, 'message' => "Inserted row with id: " . $this->conn->lastInsertId()];
        } else {
            $return = ['success' => false, 'message' => "Failed to insert row"];
        }
        $this->jsonResponse($return);
        exit;
    }

    public function deleteMedlemFromSegling()
    {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        $seglingId = $data['segling_id'] ?? null;
        $medlemId = $data['medlem_id'] ?? null;

        if ($seglingId && $medlemId) {
            // Perform the deletion operation
            $query = "DELETE FROM Segling_Medlem_Roll WHERE segling_id = :segling_id AND medlem_id = :medlem_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':segling_id', $seglingId, PDO::PARAM_INT);
            $stmt->bindParam(':medlem_id', $medlemId, PDO::PARAM_INT);
            $result = $stmt->execute();

            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Deletion failed']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
        }
    }
}
