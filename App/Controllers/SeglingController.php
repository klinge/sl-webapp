<?php

namespace App\Controllers;

use App\Models\Segling;
use App\Models\SeglingRepository;
use App\Models\MedlemRepository;
use App\Models\Roll;
use App\Utils\Session;
use Exception;
use PDO;

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
        $segling->deltagare = $segling->getDeltagare();

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
            "allaKockar" => $allaKockar
        ];
        $this->render('viewSeglingEdit', $data);
    }

    public function save(array $params)
    {
        $id = $params['id'];
        $segling = new Segling($this->conn, $id);
        var_dump($_POST);
        exit;
        //TODO complete logic for saving a segling

        //TODO add logic to save
        $segling->start_dat = $this->sanitizeInput($_POST['startdat']);
        $segling->slut_dat = $this->sanitizeInput($_POST['slutdat']);
        $segling->skeppslag = $this->sanitizeInput($_POST['skeppslag']);
        if (isset($_POST['kommentar'])) {
            $segling->kommentar = $this->sanitizeInput($_POST['kommentar']);
        }
        $segling->save();

        //TODO add error handling
        Session::setFlashMessage('success', 'Segling uppdaterad!');

        // Set the URL and redirect
        $redirectUrl = $this->router->generate('segling-list');
        header('Location: ' . $redirectUrl);
        exit;
    }
    //
    //FUNCTIONS THAT HANDLES MEMBERS ON A Segling
    //
    public function saveMedlem()
    {
        //validate input
        if (!isset($_POST['segling_id']) || !isset($_POST['segling_person']) || !isset($_POST['segling_roll'])) {
            $return = ['success' => 'false', 'data' => "Missing input"];
            $this->jsonResponse($return);
            exit;
        }

        $query = "INSERT INTO Segling_Medlem_Roll (segling_id, medlem_id, roll_id) VALUES (:segling_id, :medlem_id, :roll_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segling_id', $_POST['segling_id'], PDO::PARAM_INT);
        $stmt->bindParam(':medlem_id', $_POST['segling_person'], PDO::PARAM_INT);
        $stmt->bindParam(':roll_id', $_POST['segling_roll'], PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $return = ['success' => 'true', 'data' => "Inserted row with id: " . $this->conn->lastInsertId()];
        } else {
            $return = ['success' => 'false', 'data' => "Failed to insert row"];
        }
        $this->jsonResponse($return);
        exit;
    }

    public function deleteMedlem()
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
