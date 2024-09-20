<?php

declare(strict_types=1);

namespace App\Controllers;

use AltoRouter;
use Exception;
use App\Models\Medlem;
use App\Models\MedlemRepository;
use App\Models\Roll;
use App\Models\BetalningRepository;
use App\Utils\Sanitizer;
use App\Utils\View;
use App\Application;

class MedlemController extends BaseController
{
    private View $view;

    public function __construct(Application $app, array $request)
    {
        parent::__construct($app, $request);
        $this->view = new View($this->app);
    }

    //Sanitizing rules for sanitizing user input for Medlem data
    private array $sanitizerRules = [
        'id' => 'int',
        'fodelsedatum' => ['date', 'Y-m-d'],
        'fornamn' => 'string',
        'efternamn' => 'string',
        'email' => 'email',
        'mobil' => 'string',
        'telefon' => 'string',
        'adress' => 'string',
        'postnummer' => 'string',
        'postort' => 'string',
        'kommentar' => 'string',
        'godkant_gdpr' => 'string',
        'pref_kommunikation' => 'string',
        'isAdmin' => 'string',
    ];

    public function list(): void
    {
        $medlemRepo = new MedlemRepository($this->conn);
        $result = $medlemRepo->getAll();

        //Put everyting in the data variable that is used by the view
        $data = [
            "title" => "Medlemmar",
            "items" => $result,
            'newAction' => $this->app->getRouter()->generate('medlem-new')
        ];
        $this->view->render('viewMedlem', $data);
    }

    public function listJson(): void
    {
        $medlemRepo = new MedlemRepository($this->conn);
        $result = $medlemRepo->getAll();
        $this->jsonResponse($result);
        exit;
    }

    public function edit(array $params): void
    {
        $id = (int) $params['id'];

        //Fetch member data
        try {
            $medlem = new Medlem($this->conn, $id);
            $roll = new Roll($this->conn);
            //Fetch roles and seglingar to use in the view
            $roller = $roll->getAll();
            $seglingar = $medlem->getSeglingar();
            //fetch betalningar for member
            $betalRepo = new BetalningRepository($this->conn);
            $betalningar = $betalRepo->getBetalningForMedlem($id);

            $data = [
                "title" => "Visa medlem",
                "items" => $medlem,
                "roles" => $roller,
                'seglingar' => $seglingar,
                'betalningar' => $betalningar,
                //Used in the view to set the proper action url for the form
                'formAction' => $this->app->getRouter()->generate('medlem-save', ['id' => $id]),
                'createBetalningAction' => $this->app->getRouter()->generate('betalning-medlem', ['id' => $id]),
                'listBetalningAction' => $this->app->getRouter()->generate('betalning-medlem', ['id' => $id]),
                'deleteAction' => $this->app->getRouter()->generate('medlem-delete')
            ];
            $this->view->render('viewMedlemEdit', $data);
        } catch (Exception $e) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Kunde inte hämta medlem!'];
            $redirectUrl = $this->app->getRouter()->generate('medlem-list');
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    public function save(array $params): void
    {
        $id = (int) $params['id'];
        $medlem = new Medlem($this->conn, $id);
        $postData = $_POST;

        $result = $this->prepareAndSanitizeMedlemData($medlem, $postData);

        ///If the sanitization fails, just exit
        if (!$result) {
            exit;
        }

        try {
            $medlem->save();
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Medlem ' . $medlem->fornamn . ' ' . $medlem->efternamn . ' uppdaterad!'
            ];
        } catch (Exception $e) {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Kunde inte uppdatera medlem! Fel: ' . $e->getMessage()
            ];
        }

        // Set the URL and redirect
        $redirectUrl = $this->app->getRouter()->generate('medlem-list');
        header('Location: ' . $redirectUrl);
        exit;
    }

    public function new(): void
    {
        $roll = new Roll($this->conn);
        $roller = $roll->getAll();
        //Just show the form to add a new member
        $data = [
            "title" => "Lägg till medlem",
            "roller" => $roller,
            //Used in the view to set the proper action url for the form
            'formAction' => $this->app->getRouter()->generate('medlem-create')
        ];
        $this->view->render('viewMedlemNew', $data);
    }

    public function insertNew(): void
    {
        $medlem = new Medlem($this->conn);
        $postData = $_POST;

        $result = $this->prepareAndSanitizeMedlemData($medlem, $postData);
        //If the sanitize function returns false, we have an error, just exit
        if (!$result) {
            exit;
        }

        try {
            $medlem->create();
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Medlem ' . $medlem->fornamn . ' ' . $medlem->efternamn . ' skapad!'
            ];
        } catch (Exception $e) {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Kunde inte skapa medlem!'
            ];
        }

        // Set the URL and redirect
        $redirectUrl = $this->app->getRouter()->generate('medlem-list');
        header('Location: ' . $redirectUrl);
        exit;
    }


    public function delete(): void
    {
        $id = $_POST['id'];
        try {
            $medlem = new Medlem($this->conn, $id);
            $medlem->delete();
            $_SESSION['flash_message'] = ['type' => 'ok', 'message' => 'Medlem borttagen!'];
            // Set the URL and redirect
            $redirectUrl = $this->app->getRouter()->generate('medlem-list');
            header('Location: ' . $redirectUrl);
            exit;
        } catch (Exception $e) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Kunde inte ta bort medlem!'];
            $redirectUrl = $this->app->getRouter()->generate('medlem-list');
        }
        header('Location: ' . $redirectUrl);
        exit;
    }

    private function prepareAndSanitizeMedlemData(Medlem $medlem, array $postData): bool
    {
        $errors = [];
        $requiredFields = ['fornamn', 'efternamn', 'fodelsedatum'];
        $checkboxFields = ['godkant_gdpr', 'pref_kommunikation', 'isAdmin'];

        //Sanitize user input
        $sanitizer = new Sanitizer();
        $cleanValues = $sanitizer->sanitize($postData, $this->sanitizerRules);

        //Save roller because the sanitizer removes them from the array
        $roller = $postData['roller'];

        // Validate required fields
        foreach ($requiredFields as $field) {
            if (empty($cleanValues[$field])) {
                $errors[] = $field;
            }
        }
        // If there were errors show a flash message and redirect back to the form
        if ($errors) {
            $errorMsg = "Följande obligatoriska fält måste fyllas i: " . implode(', ', $errors);
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => $errorMsg];
            if (isset($medlem->id)) {
                $redirectUrl = $this->app->getRouter()->generate('medlem-edit', ['id' => $medlem->id]);
            } else {
                $redirectUrl = $this->app->getRouter()->generate('medlem-new');
            }
            header('Location: ' . $redirectUrl);
            return false;
        }

        //Loop over everything in POST and set values on the Medlem object
        foreach ($cleanValues as $key => $value) {
            if (property_exists($medlem, $key) && !in_array($key, $checkboxFields)) {
                // Assign value to corresponding property, checkoxes are handled below
                $medlem->$key = $value;
            }
        }

        // If checkboxes are not checked they don't exist in $_POST so set to False/0
        foreach ($checkboxFields as $field) {
            $medlem->$field = isset($cleanValues[$field]) ? 1 : 0;
        }

        //Update the medlem's roles
        if (isset($roller)) {
            $medlem->updateMedlemRoles($roller);
        }

        return true;
    }
}
