<?php

require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Medlem.php';
require_once __DIR__ . '/../models/MedlemRepository.php';
require_once __DIR__ . '/../models/Roll.php';
require_once __DIR__ . '/../models/BetalningRepository.php';

class MedlemController extends BaseController
{

  public function list()
  {
    $medlemRepo = new MedlemRepository($this->conn);
    $result = $medlemRepo->getAll();

    //Put everyting in the data variable that is used by the view
    $data = array(
      "title" => "Medlemmar",
      "items" => $result
    );
    require __DIR__ . '/../views/viewMedlem.php';
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
        'deleteAction' => $this->router->generate('medlem-delete')
      );
      require __DIR__ . '/../views/viewMedlemEdit.php';
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

    foreach ($_POST as $key => $value) {
      //Special handling for roller that is an array of ids
      if ($key === 'roller') {
        $medlem->updateMedlemRoles($value);
      } elseif (property_exists($medlem, $key)) {
        $_POST[$key] = $this->sanitizeInput($value);
        $medlem->$key = $value; // Assign value to corresponding property
      }
    }
    $medlem->save();
    //TODO add error handling
    $_SESSION['flash_message'] = array('type' => 'ok', 'message' => 'Medlem uppdaterad!');

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
    require __DIR__ . '/../views/viewMedlemNew.php';
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
    $medlem->create();
    //TODO add error handling
    $_SESSION['flash_message'] = array('type' => 'ok', 'message' => 'Medlem skapad!');

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
