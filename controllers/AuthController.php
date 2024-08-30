<?php

require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Medlem.php';
require_once __DIR__ . '/../utils/Session.php';

class AuthController extends BaseController
{

    public function showLogin()
    {
        require __DIR__ . '/../views/viewLogin.php';
    }

    public function login()
    {
        $providedEmail = $_POST['email'];
        $providedPassword = $_POST['password'];

        $stmt = $this->conn->prepare("SELECT * FROM medlem WHERE email = :email");
        $stmt->bindParam(':email', $providedEmail);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            echo "Invalid email or password";
            return false;
        }
        $medlem = new Medlem($result['id']);
        if ($medlem && password_verify($providedPassword, $medlem->password)) {
            Session::start();
            Session::set('user_id', $medlem->id);
            Session::set('fornamn', $medlem->fornamn);
            header('Location: /');
            return true;
        } else {
            echo "Invalid email or password";
            return false;
        }
    }
}
