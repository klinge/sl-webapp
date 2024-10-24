<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\BetalningRepository;
use App\Models\Segling;
use App\Models\SeglingRepository;
use App\Models\MedlemRepository;
use App\Models\Roll;
use App\Utils\Sanitizer;
use App\Utils\Session;
use App\Utils\View;
use App\Application;
use Exception;
use PDO;
use PDOException;
use Psr\Http\Message\ServerRequestInterface;

class SeglingController extends BaseController
{
    private View $view;

    public function __construct(Application $app, ServerRequestInterface $request)
    {
        parent::__construct($app, $request);
        $this->view = new View($this->app);
    }

    public function list()
    {
        $seglingar = new SeglingRepository($this->conn);
        $result = $seglingar->getAllWithDeltagare();

        //Put everyting in the data variable that is used by the view
        $data = [
            "title" => "Bokningslista",
            "newAction" => $this->app->getRouter()->generate('segling-show-create'),
            "items" => $result
        ];
        $this->view->render('viewSegling', $data);
    }

    public function edit(array $params)
    {
        $id = (int) $params['id'];
        $formAction = $this->app->getRouter()->generate('segling-save', ['id' => $id]);
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
        $year = (int) substr($segling->start_dat, 0, 4);
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
        $medlemmar = new MedlemRepository($this->conn, $this->app);
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
        $this->view->render('viewSeglingEdit', $data);
    }

    public function save(array $params)
    {
        $id = (int) $params['id'];
        $segling = new Segling($this->conn, $id);

        //Sanitize user input
        $sanitizer = new Sanitizer();
        $rules = [
            'startdat' => ['date', 'Y-m-d'],
            'slutdat' => ['date', 'Y-m-d'],
            'skeppslag' => 'string',
            'kommentar' => 'string',
        ];
        $cleanValues = $sanitizer->sanitize($this->request->getParsedBody(), $rules);

        //Store sanitized values
        $segling->start_dat = $cleanValues['startdat'];
        $segling->slut_dat = $cleanValues['slutdat'];
        $segling->skeppslag = $cleanValues['skeppslag'];
        $segling->kommentar = $cleanValues['kommentar'] ?: null;

        if ($segling->save()) {
            // Set the URL and redirect
            Session::setFlashMessage('success', 'Segling uppdaterad!');
            $redirectUrl = $this->app->getRouter()->generate('segling-list');
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
        $id = (int) $params['id'];
        $segling = new Segling($this->conn, $id);
        if ($segling->delete()) {
            Session::setFlashMessage('success', 'Seglingen är nu borttagen!');
            $this->app->getLogger()->info('Segling was deleted: ' . $segling->id . '/' . $segling->skeppslag . ' by user: ' .
                Session::get('user_id'));
            exit;
        } else {
            Session::setFlashMessage('error', 'Kunde inte ta bort seglingen. Försök igen.');
            $this->app->getLogger()->warning('Failed to delete segling was: ' . $segling->id . '/' . $segling->skeppslag .
                '  User: ' . Session::get('user_id'));
            exit;
        }
    }

    public function showCreate()
    {
        $formAction = $this->app->getRouter()->generate('segling-create');
        $data = [
            "title" => "Skapa ny segling",
            "formUrl" => $formAction
        ];
        $this->view->render('viewSeglingNew', $data);
    }

    public function create()
    {
        $sanitizer = new Sanitizer();
        $rules = [
            'startdat' => ['date', 'Y-m-d'],
            'slutdat' => ['date', 'Y-m-d'],
            'skeppslag' => 'string',
            'kommentar' => 'string',
        ];
        $cleanValues = $sanitizer->sanitize($this->request->getParsedBody(), $rules);

        //Check if requires indata is there, fail otherwise
        if (empty($cleanValues['startdat']) || empty($cleanValues['slutdat']) || empty($cleanValues['skeppslag'])) {
            $return = ['success' => false, 'message' => 'Indata saknades. Kunde inte spara seglingen. Försök igen.'];
            $this->showCreate();
            exit;
        }
        $segling = new Segling($this->conn);
        $segling->start_dat = $cleanValues['startdat'];
        $segling->slut_dat = $cleanValues['slutdat'];
        $segling->skeppslag = $cleanValues['skeppslag'];
        $segling->kommentar = $cleanValues['kommentar'];
        $result = $segling->create();

        if ($result) {
            Session::setFlashMessage('success', 'Seglingen är nu skapad!');
            $redirectUrl = $this->app->getRouter()->generate('segling-edit', ['id' => $result]);
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            $return = ['success' => false, 'message' => 'Kunde inte spara till databas. Försök igen.'];
            $this->showCreate();
            exit;
        }
    }

    /*
    * FUNCTIONS THAT HANDLE Members on a Segling
    * called from ajax calls in the client to return json data
    */
    public function saveMedlem(): array
    {
        $parsedBody = $this->request->getParsedBody();
        //validate input
        if (!isset($parsedBody['segling_id']) || !isset($parsedBody['segling_person'])) {
            $return = ['success' => false, 'message' => "Missing input"];
            $this->jsonResponse($return);
            exit;
        }
        //check if Medlem is already on the segling
        $query = "SELECT * FROM Segling_Medlem_Roll WHERE segling_id = :segling_id AND medlem_id = :medlem_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segling_id', $parsedBody['segling_id'], PDO::PARAM_INT);
        $stmt->bindParam(':medlem_id', $parsedBody['segling_person'], PDO::PARAM_INT);
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
        $hasRole = ($parsedBody['segling_roll'] == 999) ? false : true;
        if ($hasRole) {
            $query = "INSERT INTO Segling_Medlem_Roll (segling_id, medlem_id, roll_id) VALUES (:segling_id, :medlem_id, :roll_id)";
        } else {
            $query = "INSERT INTO Segling_Medlem_Roll (segling_id, medlem_id) VALUES (:segling_id, :medlem_id)";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segling_id', $parsedBody['segling_id'], PDO::PARAM_INT);
        $stmt->bindParam(':medlem_id', $parsedBody['segling_person'], PDO::PARAM_INT);
        if ($hasRole) {
            $stmt->bindParam(':roll_id', $parsedBody['segling_roll'], PDO::PARAM_INT);
        }
        //Then try to update anc catch PDO exceptions..
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            $return = ['success' => false, 'message' => "PDO error: " . $e->getMessage()];
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
                $this->app->getLogger()->info("Delete medlem from segling. Medlem: " . $medlemId . " Segling: " . $seglingId .
                    "User: " . Session::get('user_id'));
                echo json_encode(['status' => 'ok']);
            } else {
                $this->app->getLogger()->warning("Failed to delete medlem from segling. Medlem: " . $medlemId . " Segling: " . $seglingId);
                echo json_encode(['status' => 'fail', 'error' => 'Deletion failed']);
            }
        } else {
            $this->app->getLogger()->warning("Failed to delete medlem from segling. Invalid data. Medlem: " . $medlemId . " Segling: " . $seglingId);
            echo json_encode(['status' => 'fail', 'error' => 'Invalid data']);
        }
    }
}
