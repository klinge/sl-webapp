<?php

namespace App\Controllers;

use App\Utils\Session;
use App\Utils\Email;
use App\Utils\EmailType;
use App\Models\Medlem;
use App\Utils\Sanitizer;
use App\Utils\TokenHandler;
use App\Utils\TokenType;
use PDO;
use PHPMailer\PHPMailer\Exception;

class AuthController extends BaseController
{
    private ?TokenHandler $tokenHandler = null;

    public function showLogin()
    {
        $this->render('login/viewLogin');
    }

    public function login(): void
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
            Session::set('user_id', $medlem->id);
            Session::set('fornamn', $medlem->fornamn);
            if ($medlem->isAdmin) {
                Session::set('isAdmin', true);
            }
            //Check if there is a redirect url and if so redirect the user back there otherwise to homepage
            $redirectUrl = Session::get('redirect_url') ?? $this->app->getBaseUrl();
            Session::remove('redirect_url');

            header('Location: ' . $redirectUrl);
        } else {
            Session::setFlashMessage('error', 'Felaktig e-postadress eller lösenord! FELLÖSEN');
            $this->render('login/viewLogin');
        }
    }

    public function logout(): void
    {
        Session::remove('user_id');
        Session::remove('fornamn');
        Session::destroy();
        $redirectUrl = $this->router->generate('show-login');
        header('Location: ' . $redirectUrl);
        exit;
    }

    public function register(): void
    {
        //Start by sanitizing email and validating passwords
        $s = new Sanitizer();
        $rules = ['email' => 'email'];
        $cleanValues = $s->sanitize($_POST, $rules);

        $email = $cleanValues['email'];
        $password = $_POST['password'];
        $repeatPassword = $_POST['passwordRepeat'];

        //First validate that the passwords match
        if ($password != $repeatPassword) {
            Session::setFlashMessage('error', 'Lösenorden matchar inte!');
            $this->render('login/viewLogin');
            return;
        }

        //Then check that it follows some basic rules
        $passwordErrors = $this->validatePassword($password, $email);
        if (!empty($passwordErrors)) {
            $message = "<ul>";
            foreach ($passwordErrors as $error) {
                $message = $message . "<li>" . $error . "</li>";
            }
            $message = $message . "</ul>";
            Session::setFlashMessage('error', $message);
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

        $token = $this->getTokenHandler()->generateToken();
        $result = $this->getTokenHandler()->saveToken($token, TokenType::ACTIVATION, $email, $hashedPassword);

        //Fail if we couldn't save token
        if (!$result) {
            Session::setFlashMessage('error', 'Något gick fel vid registreringen. Försök igen.');
            $this->render('login/viewLogin');
            return;
        }

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
    }

    public function activate(array $params)
    {
        $token = $params['token'];
        $token_result = $this->getTokenHandler()->isValidToken($token, TokenType::ACTIVATION);

        if (!$token_result['success']) {
            Session::setFlashMessage('error', $token_result['message']);
            header('Location: ' . $this->createUrl('login'));
            return;
        }
        //If all is well add the hashed password to the member table and delete the token
        $member = $this->getMemberByEmail($token_result['email']);
        //If all is okay, add password to Medlem table
        $this->saveMembersPassword($token_result['hashedPassword'], $member['email']);

        //Delete used token, also take the chance to do some cleanup and delete all expired tokens
        $this->getTokenHandler()->deleteToken($token);
        $this->getTokenHandler()->deleteExpiredTokens();

        Session::setFlashMessage('success', 'Ditt konto är nu aktiverat. Du kan nu logga in.');
        header('Location: ' . $this->createUrl('login'));
        return;
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
            $token = $this->getTokenHandler()->generateToken();
            $result = $this->getTokenHandler()->saveToken($token, TokenType::RESET, $email);

            if (!$result) {
                Session::setFlashMessage('error', 'Kunde inte skapa token. Försök igen.');
                $this->render('login/viewReqPassword');
                exit;
            }

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
        $result = $this->getTokenHandler()->isValidToken($token, TokenType::RESET);
        if ($result['success']) {
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

        //Then verify that it follows some basic rules
        $passwordErrors = $this->validatePassword($password, $email);

        if (!empty($passwordErrors)) {
            $message = "<ul>";
            foreach ($passwordErrors as $error) {
                $message = $message . "<li>" . $error . "</li>";
            }
            $message = $message . "</ul>";
            Session::setFlashMessage('error', $message);
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
        $this->saveMembersPassword($hashedPassword, $email);
        //Delete the used token
        $this->getTokenHandler()->deleteToken($token);
        // And logout the user, which sends him to the login screen
        // TODO This flash message does not work because logout() kills the session..
        Session::setFlashMessage('success', 'Ditt lösenord är uppdaterat. Du kan nu logga in med ditt nya lösenord.');
        $this->logout();
        return;
    }

    private function getTokenHandler(): TokenHandler
    {
        if ($this->tokenHandler === null) {
            $this->tokenHandler = new TokenHandler($this->conn);
        }
        return $this->tokenHandler;
    }
    private function getMemberByEmail(string $email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM medlem WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    private function saveMembersPassword(string $hashedPassword, string $email): bool
    {
        $stmt = $this->conn->prepare("UPDATE medlem SET password = :password WHERE email = :email");
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            return true;
        } else {
            return false;
        }
    }

    private function validatePassword(string $password, string $email): array
    {
        $errors = [];
        //BASIC VALIDATION
        if (strlen($password) < 8) {
            $errors[] = "Lösenordet måste vara minst 8 tecken.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Lösenordet måste innehålla minst en stor bokstav.";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Lösenordet måste innehålla minst en liten bokstav.";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Lösenordet måste innehålla minst en siffra";
        }
        //if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        //    $errors[] = "Lösenordet måste innehålla minst ett specialtecken.";
        //}
        //CHECK THAT USERNAME OR NAME IS NOT PART OF PASSWORD
        $username = strstr($email, '@', true);
        $lowercasePassword = strtolower($password);

        if (strpos($lowercasePassword, strtolower($username)) !== false) {
            $errors[] = "Lösenordet får inte innehålla delar från din mailadress.";
        }
        // Check for firstname and lastname if email contains a period
        if (strpos($username, '.') !== false) {
            list($firstName, $lastName) = explode('.', $username, 2);

            if (strpos($lowercasePassword, strtolower($firstName)) !== false) {
                $errors[] = "Lösenordet får inte innehålla ditt förnamn.";
            }
            if (strpos($lowercasePassword, strtolower($lastName)) !== false) {
                $errors[] = "Lösenordet får inte innehålla ditt efternamn.";
            }
        }

        return $errors;
    }
}
