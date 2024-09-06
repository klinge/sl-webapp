<?php

namespace App\Controllers;

use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Utils\Session;
use App\Utils\Email;
use App\Utils\EmailType;
use App\Models\Medlem;

class AuthController extends BaseController
{
    public function showLogin()
    {
        $this->render('login/viewLogin');
    }

    public function login()
    {
        $providedEmail = $_POST['email'];
        $providedPassword = $_POST['password'];

        $result = $this->getMemberByEmail($providedEmail);

        //User not found
        if (!$result) {
            Session::setFlashMessage('error', 'Felaktig e-postadress eller lösenord! INTEIDB');
            $this->render('login/viewLogin');
        }
        //Catch exception if medlem not found
        try {
            $medlem = new Medlem($this->conn, $result['id']);
        } catch (Exception $e) {
            Session::setFlashMessage('error', 'Felaktig e-postadress eller lösenord! KUNDEINTESKAPA');
            $this->render('login/viewLogin');
        }
        //Verify provided password with password from db
        if (password_verify($providedPassword, $medlem->password)) {
            Session::start();
            Session::set('user_id', $medlem->id);
            Session::set('fornamn', $medlem->fornamn);
            if ($medlem->isAdmin) {
                Session::set('isAdmin', true);
            }
            //Check if there is a redirect url and if so redirect the user back there otherwise to homepage
            $redirectUrl = Session::get('redirect_url') ?? false;
            if ($redirectUrl) {
                Session::remove('redirect_url');
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                header('Location: ' . $this->app->getBaseUrl());
                return true;
            }
        } else {
            Session::setFlashMessage('error', 'Felaktig e-postadress eller lösenord! FELLÖSEN');
            $this->render('login/viewLogin');
        }
    }

    public function logout()
    {
        Session::remove('user_id');
        Session::remove('fornamn');
        Session::destroy();
        $redirectUrl = $this->router->generate('show-login');
        header('Location: ' . $redirectUrl);
        exit;
    }

    public function register()
    {

        $email = $_POST['email'];
        $password = $_POST['password'];
        $repeatPassword = $_POST['passwordRepeat'];
        //First validate that the passwords match
        if ($password != $repeatPassword) {
            Session::setFlashMessage('error', 'Lösenorden matchar inte!');
            $this->render('login/viewLogin');
            return;
        }
        //Then check if the user exists and already has a password
        $result = $this->getMemberByEmail($email);
        //Fail if user does not exist
        if (!$result) {
            Session::setFlashMessage('error', 'Det finns ingen medlem med den emailadressen. Du måste vara medlem för att kunna registrera dig.');
            $this->render('login/viewLogin');
            return;
        }

        $medlem = new Medlem($this->conn, $result['id']);

        //Fail if user already has a password
        if ($medlem->password) {
            Session::setFlashMessage('error', 'Konto redan registrerat. Prova att byta lösenord.');
            $this->render('login/viewLogin');
            return;
        }
        //Save hashed password and generate a token to be sent by mail to the user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        //$token = bin2hex(random_bytes(16));
        //Changed to make url-safe tokens only containing alphanumeric characters
        $token = preg_replace('/[^A-Za-z0-9]/', '', base64_encode(random_bytes(18)));
        $token_type = 'activate';

        //Add values to AuthToken table
        $stmt = $this->conn->prepare(
            "INSERT INTO AuthToken (email, token, token_type, password_hash) VALUES (:email, :token, :token_type, :password_hash)"
        );
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':token_type', $token_type);
        $stmt->bindParam(':password_hash', $hashedPassword);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            //Successful update, send email with token
            $mailer = new Email($this->app);
            $data = [
                'token' => $token,
                'fornamn' => $medlem->fornamn,
                'activate_url' => $this->createUrl('register-activate', ['token' => $token])
            ];

            try {
                $mailer->send(EmailType::TEST, $email, data: $data);
                Session::setFlashMessage(
                    'success',
                    'E-post med verifieringslänk har skickats till din e-postadress. Klicka på länken i e-posten för att aktivera ditt konto.'
                );
                $this->render('login/viewLogin');
                return;
            } catch (Exception $e) {
                Session::setFlashMessage('error', 'Kunde inte skicka mail med aktiveringslänk. Försök igen. (' . $e->getMessage() . ')');
                $this->render('login/viewLogin');
                return;
            }
        } else {
            Session::setFlashMessage('error', 'Något gick fel vid registreringen. Försök igen.');
            $this->render('login/viewLogin');
            return;
        }
    }

    public function activate(array $params)
    {
        $token = $params['token'];
        $result = $this->isValidToken($token, 'activate');
        if ($result['valid']) {
            //If all is okay, add password to Medlem table
            $stmt = $this->conn->prepare("UPDATE medlem SET password = :password WHERE email = :email");
            $stmt->bindParam(':password', $result['password_hash']);
            $stmt->bindParam(':email', $result['email']);
            $stmt->execute();
            //Delete token from db
            $stmt = $this->conn->prepare("DELETE FROM AuthToken WHERE token = :token");
            $stmt->bindParam(':token', $token);
            $stmt->execute();

            //Also take the chance to do some cleanup and delete all expired tokens
            $deletedRows = $this->deleteExpiredTokens();

            Session::setFlashMessage('success', 'Ditt konto är nu aktiverat. Du kan nu logga in.');
            header('Location: ' . $this->createUrl('login'));
            return;
        } else {
            Session::setFlashMessage('error', $result['message']);
            header('Location: ' . $this->createUrl('login'));
            return;
        }
    }

    public function showRequestPwd()
    {
        $this->render('login/viewReqPassword');
    }

    public function sendPwdRequestToken()
    {
        $email = $_POST['email'];
        $member = $this->getMemberByEmail($email);
        //Don't do anything if member doesn't exist
        if ($member) {
            //Generate a token and save it in the database
            $token = preg_replace('/[^A-Za-z0-9]/', '', base64_encode(random_bytes(20)));
            $token_type = 'reset';

            $stmt = $this->conn->prepare(
                "INSERT INTO AuthToken (email, token, token_type) VALUES (:email, :token, :token_type)"
            );
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':token_type', $token_type);
            $stmt->execute();

            $mailer = new Email($this->app);
            $data = [
                'token' => $token,
                'fornamn' => $member['fornamn'],
                'pwd_reset_url' => $this->createUrl('show-reset-password', ['token' => $token]),
            ];

            try {
                $mailer->send(EmailType::TEST, $email, data: $data);
                $this->render('login/viewLogin');
                return;
            } catch (Exception $e) {
                Session::setFlashMessage('error', 'Något gick fel vid registreringen. Försök igen. (' . $e->getMessage() . ') Länk: ' . $data['pwd_reset_url']);
                $this->render('login/viewReqPassword');
                return;
            }
        }
        //Set the same message disregarding if user existed or not
        Session::setFlashMessage('success', 'Om du har ett konto får du strax ett mail med en återställningslänk till din e-postadress.');
        $this->render('login/viewReqPassword');
    }

    public function showResetPassword(array $params)
    {
        $token = $params['token'];
        //Validate token
        $result = $this->isValidToken($token, 'reset');
        if ($result['valid']) {
            //Render set new password view
            $viewData = [
                'email' => $result['email'],
                'token' => $token
            ];
            $this->render('login/viewSetNewPassword', $viewData);
            return;
        } else {
            Session::setFlashMessage('error', $result['message']);
            header('Location: ' . $this->createUrl('show-request-password'));
            return;
        }
    }

    public function resetAndSavePassword()
    {
        $email = $_POST['email'];
        $token = $_POST['token'];
        $password = $_POST['password'];
        $password2 = $_POST['password2'];
        //Fail if passwords don't match
        if ($password !== $password2) {
            Session::setFlashMessage('error', 'Lösenorden stämmer inte överens. Försök igen');
            $viewData = [
                'email' => $email,
                'token' => $token
            ];
            $this->render('login/viewSetNewPassword', $viewData);
            return;
        }
        $member = $this->getMemberByEmail($email);
        //Fail if member doesn't exist, should never happen
        if (!$member) {
            Session::setFlashMessage('error', 'OJ! Nu blev det ett tekniskt fel. Användaren finns inte..');
            header('Location: ' . $this->createUrl('show-request-password'));
            return;
        }
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        // And save it to db
        $stmt = $this->conn->prepare("UPDATE medlem SET password = :password WHERE email = :email");
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        // Lastly remove the token
        $stmt = $this->conn->prepare("DELETE FROM AuthToken WHERE token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        // And send the user to the login page
        Session::setFlashMessage('success', 'Ditt lösenord är uppdaterat. Du kan nu logga in med ditt nya lösenord.');
        header('Location: ' . $this->createUrl('show-login'));
        return;
    }

    private function getMemberByEmail(string $email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM medlem WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    private function isValidToken(string $token, string $type)
    {
        $stmt = $this->conn->prepare("SELECT * FROM AuthToken WHERE token = :token AND token_type = :token_type");
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':token_type', $type);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            //Fail if we didnt find the token
            return ['valid' => false, 'message' => 'Länken är inte giltig'];
        } else {
            //Check if token is expired
            $expirationTime = strtotime($result['created_at']) + (60 * 30); // 30 minutes in seconds
            if (time() > $expirationTime) {
                //Also fail if token is expired
                return ['valid' => false, 'message' => 'Länkens giltighetstid är 30 min. Den fungerar inte längre. Försök igen'];
            }
            return ['valid' => true, 'email' => $result['email']];
        }
    }

    private function deleteExpiredTokens()
    {
        $stmt = $this->conn->prepare("DELETE FROM AuthToken WHERE created_at < datetime('now', '-1 hour')");
        $stmt->execute();
        //Return number of deleted rows
        return $stmt->rowCount();
    }
}
