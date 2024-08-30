<?php

require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Medlem.php';
require_once __DIR__ . '/../utils/Session.php';

class AuthController extends BaseController
{

    public function showLogin()
    {
        $this->render('/../views/viewLogin.php');
    }

    public function login()
    {
        $providedEmail = $_POST['email'];
        $providedPassword = $_POST['password'];

        $stmt = $this->conn->prepare("SELECT id FROM medlem WHERE email = :email");
        $stmt->bindParam(':email', $providedEmail);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        //User not found
        if (!$result) {
            Session::set('flash_message', array('type' => 'error', 'message' => 'Felaktig e-postadress eller lösenord! INTEIDB'));
            $this->render('/../views/viewLogin.php');
        }
        //Catch exception if medlem not found
        try {
            $medlem = new Medlem($this->conn, $result['id']);
        }
        catch (Exception $e) {
            Session::set('flash_message', array('type' => 'error', 'message' => 'Felaktig e-postadress eller lösenord! KUNDEINTESKAPA'));
            $this->render('/../views/viewLogin.php');
        }
        //Verify providedPassword with password from db
        if (password_verify($providedPassword, $medlem->password)) {
            Session::start();
            Session::set('user_id', $medlem->id);
            Session::set('fornamn', $medlem->fornamn);
            if ($medlem->isAdmin) {
                Session::set('isAdmin', true);
            }
            $this->render('/../views/home.php');  
            return true;
        } else {
            Session::set('flash_message', array('type' => 'error', 'message' => 'Felaktig e-postadress eller lösenord! FELLÖSEN'));
            $this->render('/../views/viewLogin.php');
        }
    }

    public function logout() {
        Session::remove('user_id');
        Session::remove('fornamn');
        Session::destroy();
        $redirectUrl = $this->router->generate('show-login');
        header('Location: ' . $redirectUrl);
        exit;
    }
}
