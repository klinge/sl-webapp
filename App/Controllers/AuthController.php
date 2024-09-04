<?php

namespace App\Controllers;

use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Utils\Session;
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
            Session::set('flash_message', array('type' => 'error', 'message' => 'Felaktig e-postadress eller lösenord! INTEIDB'));
            $this->render('login/viewLogin');
        }
        //Catch exception if medlem not found
        try {
            $medlem = new Medlem($this->conn, $result['id']);
        } catch (Exception $e) {
            Session::set('flash_message', array('type' => 'error', 'message' => 'Felaktig e-postadress eller lösenord! KUNDEINTESKAPA'));
            $this->render('login/viewLogin');
        }
        //Verify providedPassword with password from db
        if (password_verify($providedPassword, $medlem->password)) {
            Session::start();
            Session::set('user_id', $medlem->id);
            Session::set('fornamn', $medlem->fornamn);
            if ($medlem->isAdmin) {
                Session::set('isAdmin', true);
            }
            $this->render('home');
            return true;
        } else {
            Session::set('flash_message', array('type' => 'error', 'message' => 'Felaktig e-postadress eller lösenord! FELLÖSEN'));
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
            Session::set('flash_message', array('type' => 'error', 'message' => 'Lösenorden matchar inte!'));
            $this->render('login/viewLogin');
            return;
        }
        //Then check if the user exists and already has a password
        $result = $this->getMemberByEmail($email);
        //Fail if user does not exist
        if (!$result) {
            Session::set('flash_message', array('type' => 'error', 'message' => 'Det finns ingen medlem med den emailadressen. Du måste vara medlem för att kunna registrera dig.'));
            $this->render('login/viewLogin');
            return;
        }

        $medlem = new Medlem($this->conn, $result['id']);

        //Fail if user already has a password
        if ($medlem->password) {
            Session::set('flash_message', array('type' => 'error', 'message' => 'Konto redan registrerat. Prova att byta lösenord.'));
            $this->render('login/viewLogin');
            return;
        }
        //Save hashed password and generate a token to be sent by mail to the user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        //$token = bin2hex(random_bytes(16));
        //Changed to make url-safe tokens only containing alphanumeric characters
        $token = preg_replace('/[^A-Za-z0-9]/', '', base64_encode(random_bytes(18)));

        //Add values to AuthToken table
        $stmt = $this->conn->prepare("INSERT INTO AuthToken (email, token, password_hash) VALUES (:email, :token, :password_hash)");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':password_hash', $hashedPassword);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            //Send email with token
            try {
                $this->sendVerificationEmail($email, $token);
                Session::set('flash_message', array('type' => 'success', 'message' => 'E-post med verifieringslänk har skickats till din e-postadress. Klicka på länken i e-posten för att aktivera ditt konto.'));
                $this->render('viewLogin');
                return;
            } catch (Exception $e) {
                Session::set('flash_message', array('type' => 'error', 'message' => 'Något gick fel vid registreringen. Försök igen. (' . $e->getMessage() . ')'));
                $this->render('login/viewLogin');
                return;
            }
        } else {
            Session::set('flash_message', array('type' => 'error', 'message' => 'Något gick fel vid registreringen. Försök igen.'));
            $this->render('login/viewLogin');
            return;
        }
    }

    public function activate(array $params)
    {
        $token = $params['token'];
        //Get token from db
        $stmt = $this->conn->prepare("SELECT * FROM AuthToken WHERE token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        //Fail if token not found
        if (!$result) {
            Session::set('flash_message', array('type' => 'error', 'message' => 'Ogiltig verifieringslänk.'));
            header('Location: ' . $this->createUrl('login'));
            exit;
        }
        //Fail if token is expired
        $expirationTime = strtotime($result['created_at']) + (60 * 15); // 15 minutes in seconds
        if (time() > $expirationTime) {
            Session::set('flash_message', array('type' => 'error', 'message' => 'Verifieringslänken har gått ut. Försök igen.'));
            header('Location: ' . $this->createUrl('login'));
            exit;
        }
        //If all is okay, add password to Medlem table
        $stmt = $this->conn->prepare("UPDATE medlem SET password = :password WHERE email = :email");
        $stmt->bindParam(':password', $result['password_hash']);
        $stmt->bindParam(':email', $result['email']);
        $stmt->execute();
        //Delete token from db
        $stmt = $this->conn->prepare("DELETE FROM AuthToken WHERE token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        //Also take the chance to delete all remaining records in AuthToken older than 1 hour
        $stmt = $this->conn->prepare("DELETE FROM AuthToken WHERE created_at < datetime('now', '-1 hour')");
        $stmt->execute();

        Session::set('flash_message', array('type' => 'success', 'message' => 'Ditt konto är aktiverat. Du kan nu logga in. '));
        header('Location: ' . $this->createUrl('login'));
        exit;
    }

    public function showRequestPwd()
    {
        $this->render('login/viewReqPassword');
    }

    public function handleRequestPwd()
    {
        $email = $_POST['email'];
        $member = $this->getMemberByEmail($email);
        var_dump($member);
        exit;
    }

    protected function getMemberByEmail($email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM medlem WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    function sendVerificationEmail($email, $token)
    {
        // Replace with your desired email settings
        $senderEmail = 'info@sofialinnea.se';
        $senderName = 'Sofia Linnea Medlemsapp';
        // Construct the verification link
        $verificationLink = 'http://localhost/sl-webapp/register/' . urlencode($token);

        // Create the email content
        $subject = 'Sofia Linnea: Aktivera ditt konto';
        $message = "Välkommen till Sofia Linneas Medlemsregister. Du eller någon annan har nyligen försökt skapa en inloggning. 
        Om det var du kan du klicka länken nedan för att verifiera din epostadress och aktivera ditt konto:\n\n
        $verificationLink\n\n
        Aktiveringslänken är giltig 15 minuter.\n\n
        Hälsningar,\n
        $senderName";

        //Create mail object and connect to smtp server - using Mailtrap for testing
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->app->getConfig("SMTP_HOST");
        $mail->Port = $this->app->getConfig("SMTP_PORT"); //Valid ports are 25, 465, 587, 2525
        $mail->SMTPAuth = true;
        /*
        $mail->SMTPOptions = array(
            'ssl' => array(
                'cafile' => '/etc/ssl/certs/ca-certificates.crt',
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        */
        $mail->Timeout = 30; //set timeout to 30 seconds
        $mail->Username = $this->app->getConfig("SMTP_USERNAME");
        $mail->Password = $this->app->getConfig("SMTP_PASSWORD");
        $mail->SMTPDebug = 3;

        //Set email content
        $mail->isHTML(false);
        $mail->setFrom($senderEmail, $senderName);
        $mail->addAddress($email);
        $mail->Subject = $subject;
        $mail->Body = $message;

        // Try sending, catch errors and display them
        try {
            $mail->send();
            return true;
        } catch (Exception $e) {
            throw new Exception("Felmeddelande: {$mail->ErrorInfo}, Verify link: {$verificationLink}");
        }
    }
}
