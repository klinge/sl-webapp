<?php

namespace App\Controllers;

use Exception;
use App\Models\Medlem;
use App\Models\MedlemRepository;
use App\Models\Roll;
use App\Models\BetalningRepository;

class MedlemController extends BaseController
{

  public function list()
  {
    $medlemRepo = new MedlemRepository($this->conn);
    $result = $medlemRepo->getAll();

    //Put everyting in the data variable that is used by the view
    $data = array(
      "title" => "Medlemmar",
      "items" => $result,
      'newAction' => $this->router->generate('medlem-new')
    );
    $this->render('viewMedlem', $data);
  }

  public function edit(array $params)
  {
    $id = $params['id'];

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

      $data = array(
        "title" => "Visa medlem",
        "items" => $medlem,
        "roles" => $roller,
        'seglingar' => $seglingar,
        'betalningar' => $betalningar,
        //Used in the view to set the proper action url for the form
        'formAction' => $this->router->generate('medlem-save', ['id' => $id]),
        'createBetalningAction' => $this->router->generate('betalning-medlem', ['id' => $id]),
        'listBetalningAction' => $this->router->generate('betalning-medlem', ['id' => $id]),
        'deleteAction' => $this->router->generate('medlem-delete')
      );
      $this->render('viewMedlemEdit', $data);
    } catch (Exception $e) {
      $_SESSION['flash_message'] = array('type' => 'error', 'message' => 'Kunde inte hämta medlem!');
      $redirectUrl = $this->router->generate('medlem-list');
      header('Location: ' . $redirectUrl);
      exit;
    }
  }

  public function save(array $params)
  {
    $id = $params['id'];
    $medlem = new Medlem($this->conn, $id);

    //Start by validating fodelsedatum and fail early if not valid
    if (!$this->validateDate($_POST['fodelsedatum'])) {
      $_SESSION['flash_message'] = array('type' => 'error', 'message' => 'Felaktigt fodelsedatum!');
      $redirectUrl = $this->router->generate('medlem-edit', ['id' => $id]);
      header('Location: ' . $redirectUrl);
      exit;
    }

    //Loop over everything in POST and set values on the Medlem object

    //Loop over everything in POST and set values on the Medlem object
    foreach ($_POST as $key => $value) {
      //Special handling for roller that is an array of ids
      if ($key === 'roller') {
        $medlem->updateMedlemRoles($value);
      } elseif (property_exists($medlem, $key)) {
        $medlem->$key = $this->sanitizeInput($value); // Assign value to corresponding property
      }
    }

    //If preference checkboxes are not checked they don't exist in $_POST so set to False/0
    if (!isset($_POST['godkant_gdpr'])) {
      $medlem->godkant_gdpr = 0;
    }
    if (!isset($_POST['pref_kommunikation'])) {
      $medlem->pref_kommunikation = 0;
    }
    if (!isset($_POST['isAdmin'])) {
      $medlem->isAdmin = 0;
    }

    //After setting all values on the member object try to save it
    try {
      $medlem->save();
      $_SESSION['flash_message'] = array('type' => 'success', 'message' => 'Medlem uppdaterad!');
    } catch (Exception $e) {
      $_SESSION['flash_message'] = array('type' => 'error', 'message' => 'Kunde inte uppdatera medlem! Fel: ' . $e->getMessage());
    }

    // Set the URL and redirect
    $redirectUrl = $this->router->generate('medlem-list');
    header('Location: ' . $redirectUrl);
    exit;
  }

  public function new()
  {
    $roll = new Roll($this->conn);
    $roller = $roll->getAll();
    //Just show the form to add a new member
    $data = array(
      "title" => "Lägg till medlem",
      "roller" => $roller,
      //Used in the view to set the proper action url for the form
      'formAction' => $this->router->generate('medlem-create')
    );
    $this->render('viewMedlemNew', $data);
  }

  public function insertNew()
  {
    $medlem = new Medlem($this->conn);

    foreach ($_POST as $key => $value) {
      //Special handling for roller that is an array of ids
      if ($key === 'roller') {
        $medlem->updateMedlemRoles($value);
      } elseif (property_exists($medlem, $key)) {
        $_POST[$key] = $this->sanitizeInput($value);
        $medlem->$key = $value; // Assign value to corresponding property
      }
    }
    
    //If preference checkboxes are not checked they don't exist in $_POST so set to False/0
    if (!isset($_POST['godkant_gdpr'])) {
      $medlem->godkant_gdpr = 0;
    }
    if (!isset($_POST['pref_kommunikation'])) {
      $medlem->pref_kommunikation = 0;
    }
    if (!isset($_POST['isAdmin'])) {
      $medlem->isAdmin = 0;
    }

    try {
      $medlem->create();
      $_SESSION['flash_message'] = array('type' => 'success', 'message' => 'Medlem ". $medlem-fornamn . " " . $medlem->efternamn . " skapad!');
    } catch (Exception $e) {
      $_SESSION['flash_message'] = array('type' => 'error', 'message' => 'Kunde inte skapa medlem!');
    }


    // Set the URL and redirect
    $redirectUrl = $this->router->generate('medlem-list');
    header('Location: ' . $redirectUrl);
    exit;
  }


  public function delete()
  {
    $id = $_POST['id'];
    try {
      $medlem = new Medlem($this->conn, $id);
      $medlem->delete();
      $_SESSION['flash_message'] = array('type' => 'ok', 'message' => 'Medlem borttagen!');
      // Set the URL and redirect
      $redirectUrl = $this->router->generate('medlem-list');
      header('Location: ' . $redirectUrl);
      exit;
    } catch (Exception $e) {
      $_SESSION['flash_message'] = array('type' => 'error', 'message' => 'Kunde inte ta bort medlem!');
      $redirectUrl = $this->router->generate('medlem-list');
    }
    header('Location: ' . $redirectUrl);
    exit;
  }
}
