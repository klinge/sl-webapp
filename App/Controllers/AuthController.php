<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Medlem;
use App\Utils\Sanitizer;
use App\Utils\TokenHandler;
use App\Utils\Session;
use App\Utils\Email;
use App\Utils\EmailType;
use App\Utils\TokenType;
use App\Utils\View;
use App\Application;
use PDO;
use PHPMailer\PHPMailer\Exception;

class AuthController extends BaseController
{
    private ?TokenHandler $tokenHandler = null;
    private View $view;
    private string $siteAddress;
    private string $secret;

    //Messages
    private const RECAPTCHA_ERROR_MESSAGE = 'Kunde inte validera recaptcha. Försök igen.';
    private const BAD_EMAIL_OR_PASSWORD = 'Felaktig e-postadress eller lösenord';

    /**
     * @param Application $app The application instance.
     * @param array<string, mixed> $request The request data.
     */
    public function __construct(Application $app, array $request)
    {
        parent::__construct($app, $request);
        $this->view = new View($this->app);
        $this->siteAddress = $this->app->getConfig('SITE_ADDRESS');
        $this->secret = $this->request['RECAPTCHA_SECRET_KEY'];
    }

    /**
     * Renders the login view.
     *
     * This method sets the CSRF token and then renders the login view template.
     */
    public function showLogin(): void
    {
        $this->setCsrfToken();
        $this->view->render('login/viewLogin');
    }

    /**
     * Handles user login process.
     *
     * Validates reCAPTCHA, authenticates user credentials,
     * and manages session upon successful login.
     *
     * @throws Exception If member object creation fails
     * @return void
     */
    public function login(): void
    {
        //First validate recaptcha and send user back to login page if failed
        if (!$this->validateRecaptcha()) {
            Session::setFlashMessage('error', self::RECAPTCHA_ERROR_MESSAGE);
            $this->view->render('login/viewLogin');
        }

        $providedEmail = $_POST['email'];
        $providedPassword = $_POST['password'];

        $result = $this->getMemberByEmail($providedEmail);

        //User not found
        if (!$result) {
            $this->app->getLogger()->info("Failed login. Email not existing: " . $providedEmail . ' IP: ' . $this->request['REMOTE_ADDR']);
            Session::setFlashMessage('error', self::BAD_EMAIL_OR_PASSWORD);
            $this->view->render('login/viewLogin');
            exit;
        }
        //Catch exception if medlem not found, should not happen since we already checked for it
        try {
            $medlem = new Medlem($this->conn, $this->app->getLogger(), $result['id']);
        } catch (Exception $e) {
            $this->app->getLogger()->error("Technical error. Could not create member object for member id: " . $result['id']);
            Session::setFlashMessage('error', 'Tekniskt fel. Försök igen eller kontakta en administratör!');
            $this->view->render('login/viewLogin');
            return;
        }
        //Fail if passwork did not verify
        if (!password_verify($providedPassword, $medlem->password)) {
            $this->app->getLogger()->info("Failed login. Incorrect password for member: " . $providedEmail . ' IP: ' . $this->request['REMOTE_ADDR']);
            Session::setFlashMessage('error', self::BAD_EMAIL_OR_PASSWORD);
            $this->view->render('login/viewLogin');
            return;
        }
        // User is successfully logged in, regenerate session id because it's a safe practice
        $this->app->getLogger()->info("Member logged in. Member email: " . $medlem->email .  ' IP: ' . $this->request['REMOTE_ADDR']);
        Session::regenerateId();
        Session::set('user_id', $medlem->id);
        Session::set('fornamn', $medlem->fornamn);
        // Send admins and users to different parts of the site
        if ($medlem->isAdmin) {
            Session::set('is_admin', true);
            //Check if there is a redirect url and if so redirect the user back there otherwise to homepage
            $redirectUrl = Session::get('redirect_url') ?? $this->app->getRouter()->generate('home');
            Session::remove('redirect_url');
        } else {
            //if user is not an admin send them to the user part of the site
            $redirectUrl = $this->app->getRouter()->generate('user-home');
            Session::remove('redirect_url');
        }
        header('Location: ' . $redirectUrl);
    }

    /**
     * Handles user logout process.
     *
     * Removes user session data and redirects to the login page.
     *
     * @return void
     */
    public function logout(): void
    {
        Session::remove('user_id');
        Session::remove('fornamn');
        Session::destroy();
        $redirectUrl = $this->app->getRouter()->generate('show-login');
        header('Location: ' . $redirectUrl);
        return;
    }

    /**
     * Handles user registration process.
     *
     * Validates reCAPTCHA, sanitizes input, checks password requirements,
     * verifies user eligibility, and sends activation email.
     *
     * @return void
     */
    public function register(): void
    {
        //First validate recaptcha and send user back to login page if failed
        if (!$this->validateRecaptcha()) {
            Session::setFlashMessage('error', self::RECAPTCHA_ERROR_MESSAGE);
            $this->view->render('login/viewLogin');
        }
        //Sanitize email and validate passwords
        $s = new Sanitizer();
        $rules = ['email' => 'email'];
        $cleanValues = $s->sanitize($_POST, $rules);

        $email = $cleanValues['email'];
        $password = $_POST['password'];
        $repeatPassword = $_POST['passwordRepeat'];

        //First validate that the passwords match
        if ($password != $repeatPassword) {
            Session::setFlashMessage('error', 'Lösenorden matchar inte!');
            $this->view->render('login/viewLogin');
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
            $this->view->render('login/viewLogin');
            return;
        }

        //Then check if the user exists and already has a password
        $result = $this->getMemberByEmail($email);
        //Fail if user does not exist
        if (!$result) {
            $this->app->getLogger()->info("Register member: Failed to register new member. Email does not exist: " . $email);
            Session::setFlashMessage('error', 'Det finns ingen medlem med den emailadressen. Du måste vara medlem för att kunna registrera dig.');
            $this->view->render('login/viewLogin');
            return;
        }

        $medlem = new Medlem($this->conn, $this->app->getLogger(), $result['id']);

        //Fail if user already has a password
        if ($medlem->password) {
            $this->app->getLogger()->info("Register member: Failed to register new member. Account already activated: " . $email);
            Session::setFlashMessage('error', 'Ditt konto är redan redan registrerat. Har du glömt dit lösenord? Prova att byta lösenord.');
            $this->view->render('login/viewLogin');
            return;
        }
        //Save hashed password and generate a token to be sent by mail to the user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $token = $this->getTokenHandler()->generateToken();
        $result = $this->getTokenHandler()->saveToken($token, TokenType::ACTIVATION, $email, $hashedPassword);

        //Fail if we couldn't save token
        if (!$result) {
            $this->app->getLogger()->error("Register member: could not save token for member:" . $email);
            Session::setFlashMessage('error', 'Något gick fel vid registreringen. Försök igen.');
            $this->view->render('login/viewLogin');
            return;
        }

        //Successful update, send email with token
        $mailer = new Email($this->app);
        $data = [
            'token' => $token,
            'fornamn' => $medlem->fornamn,
            'activate_url' => $this->siteAddress . $this->app->getRouter()->generate('register-activate', ['token' => $token])
        ];

        try {
            $mailer->send(EmailType::VERIFICATION, $email, data: $data);
            Session::setFlashMessage(
                'success',
                'E-post med verifieringslänk har skickats till din e-postadress. Klicka på länken i e-posten för att aktivera ditt konto.'
            );
            $this->view->render('login/viewLogin');
            return;
        } catch (Exception $e) {
            Session::setFlashMessage('error', 'Kunde inte skicka mail med aktiveringslänk. Försök igen. (' . $e->getMessage() . ')');
            $this->view->render('login/viewLogin');
            return;
        }
    }

    /**
     * Activates a user account.
     *
     * Validates the activation token, saves the user's password,
     * and cleans up used and expired tokens.
     *
     * @param array $params Contains the activation token
     * @return void
     */
    public function activate(array $params)
    {
        $token = $params['token'];
        $token_result = $this->getTokenHandler()->isValidToken($token, TokenType::ACTIVATION);

        if (!$token_result['success']) {
            $this->app->getLogger()->warning("Activate account: failed to activate account. Token given was" . $token . ". Remote IP: " . $this->request['REMOTE_ADDR']);
            Session::setFlashMessage('error', $token_result['message']);
            header('Location: ' . $this->app->getRouter()->generate('login'));
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
        $this->app->getLogger()->info("Activated account for member: " . $member['email'] . ". IP: " . $this->request['REMOTE_ADDR']);
        header('Location: ' . $this->app->getRouter()->generate('login'));
        //TODO Send a welcome mail on successful activation
        return;
    }

    /**
     * Displays the password request form.
     *
     * @return void
     */
    public function showRequestPwd()
    {
        $this->view->render('login/viewReqPassword');
    }

    /**
     * Handles password reset request.
     *
     * Validates reCAPTCHA, generates a password reset token, saves token,
     * and sends password reset email if user exists.
     *
     * @return void
     */
    public function sendPwdRequestToken()
    {
        //First validate recaptcha and send user back to login page if failed
        if (!$this->validateRecaptcha()) {
            Session::setFlashMessage('error', self::RECAPTCHA_ERROR_MESSAGE);
            $this->view->render('login/viewReqPassword');
        }
        $email = $_POST['email'];
        $member = $this->getMemberByEmail($email);
        //Don't do anything if member doesn't exist
        if ($member) {
            $token = $this->getTokenHandler()->generateToken();
            $result = $this->getTokenHandler()->saveToken($token, TokenType::RESET, $email);
            $this->app->getLogger()->info("Reset password called for user: " . $email . ". Remote IP: " . $this->request['REMOTE_ADDR']);

            if (!$result) {
                Session::setFlashMessage('error', 'Kunde inte skapa token. Försök igen.');
                $this->view->render('login/viewReqPassword');
                return;
            }

            $mailer = new Email($this->app);
            $data = [
                'token' => $token,
                'fornamn' => $member['fornamn'],
                'pwd_reset_link' => $this->siteAddress . $this->app->getRouter()->generate('show-reset-password', ['token' => $token]),
            ];

            try {
                $mailer->send(EmailType::PASSWORD_RESET, $email, data: $data);
            } catch (Exception $e) {
                Session::setFlashMessage('error', 'Något gick fel vid registreringen. Försök igen. (' . $e->getMessage() . ') Länk: ' . $data['pwd_reset_link']);
                $this->view->render('login/viewReqPassword');
                return;
            }
        } else {
            $this->app->getLogger()->warning("Reset password called for non-existing user: " . $email . ". Remote IP: " . $this->request['REMOTE_ADDR']);
        }
        //Set the same message disregarding if user existed or not
        Session::setFlashMessage('success', 'Om du har ett konto får du strax ett mail med en återställningslänk till din e-postadress.');
        $this->view->render('login/viewReqPassword');
    }

    /**
     * Displays the reset password form.
     *
     * Validates the reset token and renders the new password form
     * or redirects to password request page on invalid token.
     *
     * @param array $params Contains the password reset token
     * @return void
     */
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
            $this->setCsrfToken();
            $this->view->render('login/viewSetNewPassword', $viewData);
            return;
        } else {
            Session::setFlashMessage('error', $result['message']);
            header('Location: ' . $this->app->getRouter()->generate('show-request-password'));
            return;
        }
    }

    /**
     * Resets and saves a new password for the user.
     *
     * Validates password match and complexity, updates the password,
     * deletes the used token, and logs out the user.
     *
     * @return void
     */
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
            $this->view->render('login/viewSetNewPassword', $viewData);
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
            $this->view->render('login/viewSetNewPassword', $viewData);
            return;
        }

        $member = $this->getMemberByEmail($email);

        //Fail if member doesn't exist, should never happen
        if (!$member) {
            Session::setFlashMessage('error', 'OJ! Nu blev det ett tekniskt fel. Användaren finns inte..');
            header('Location: ' . $this->app->getRouter()->generate('show-request-password'));
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

    /**
     * Retrieves or initializes the TokenHandler instance.
     *
     * @return TokenHandler
     */
    private function getTokenHandler(): TokenHandler
    {
        if ($this->tokenHandler === null) {
            $this->tokenHandler = new TokenHandler($this->conn);
        }
        return $this->tokenHandler;
    }

    /**
     * Retrieves member data by email.
     *
     * @param string $email The email address of the member
     * @return array|bool Member data array or false if not found
     */
    private function getMemberByEmail(string $email): array|bool
    {
        $stmt = $this->conn->prepare("SELECT * FROM medlem WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Saves a new hashed password for a member.
     *
     * @param string $hashedPassword The new hashed password
     * @param string $email The email address of the member
     * @return bool True if password was successfully updated, false otherwise
     */
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

    /**
     * Validates the reCAPTCHA response.
     *
     * Verifies the Google reCAPTCHA response against the expected hostname
     * and score threshold. Logs the verification result.
     *
     * @return bool True if reCAPTCHA verification succeeds, false otherwise
     */
    private function validateRecaptcha(): bool
    {
        $gRecaptchaResponse = $_POST['g-recaptcha-response'];
        $remoteIp = $_SERVER['REMOTE_ADDR'];
        $recaptcha = new \ReCaptcha\ReCaptcha($this->secret);
        $resp = $recaptcha->setExpectedHostname($_SERVER['SERVER_NAME'])
            ->setScoreThreshold(0.5)
            ->verify($gRecaptchaResponse, $remoteIp);
        $this->app->getLogger()->info("Recaptcha: score: " . $resp->getScore() . ", called from: " . $_SERVER['REMOTE_ADDR']);
        return $resp->isSuccess();
    }

    /**
     * Validates a password against complexity requirements.
     *
     * Checks if the password meets the minimum length, contains uppercase,
     * lowercase, numeric characters, and does not include parts of the email.
     *
     * @param string $password The password to validate
     * @param string $email The user's email address
     * @return array An array of error messages, or an empty array if valid
     */
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
