<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Application;
use App\Models\MedlemRepository;
use App\Models\Medlem;
use App\Services\Auth\PasswordService;
use App\Utils\View;
use App\Utils\Session;
use Psr\Http\Message\ServerRequestInterface;

class LoginController extends AuthBaseController
{
    private View $view;
    private MedlemRepository $medlemRepo;
    private PasswordService $passwordService;

    //Messages
    protected const BAD_EMAIL_OR_PASSWORD = 'Felaktig e-postadress eller lösenord';

    //View links
    private const LOGIN_VIEW = 'login/viewLogin';

    /**
     * @param Application $app The application instance.
     * @param ServerRequestInterface $request The request data.
     */
    public function __construct(Application $app, ServerRequestInterface $request)
    {
        parent::__construct($app, $request);
        $this->view = new View($this->app);
        $this->passwordService = new PasswordService();
        $this->medlemRepo = new MedlemRepository($this->conn, $this->app);
    }

    /**
     * Renders the login view.
     *
     * This method sets the CSRF token and then renders the login view template.
     */
    public function showLogin(): void
    {
        $this->setCsrfToken();
        $this->view->render(self::LOGIN_VIEW);
    }

    /**
     * Handles user login process.
     *
     * Validates reCAPTCHA, authenticates user credentials,
     * and manages session upon successful login.
     *
     * @return void
     */
    public function login(): void
    {
        //First validate recaptcha and send user back to login page if failed
        if (!$this->validateRecaptcha()) {
            Session::setFlashMessage('error', self::RECAPTCHA_ERROR_MESSAGE);
            $this->view->render(self::LOGIN_VIEW);
        }

        $providedEmail = $this->request->getParsedBody()['email'] ?? '';
        $providedPassword = $this->request->getParsedBody()['password'] ?? '';

        if (empty($providedEmail) || empty($providedPassword)) {
            $this->app->getLogger()->info("Failed login. Empty email or password. IP: " . $this->remoteIp);
            Session::setFlashMessage('error', self::BAD_EMAIL_OR_PASSWORD);
            $this->view->render(self::LOGIN_VIEW);
            exit;
        }

        $result = $this->medlemRepo->getMemberByEmail($providedEmail);

        //User not found
        if (!$result) {
            $this->app->getLogger()->info("Failed login. Email not existing: " . $providedEmail . ' IP: ' . $this->remoteIp);
            Session::setFlashMessage('error', self::BAD_EMAIL_OR_PASSWORD);
            $this->view->render(self::LOGIN_VIEW);
            exit;
        }
        //Catch exception if medlem not found, should not happen since we already checked for it
        try {
            $medlem = new Medlem($this->conn, $this->app->getLogger(), $result['id']);
        } catch (\Exception $e) {
            $this->app->getLogger()->error("Technical error. Could not create member object for member id: " . $result['id']);
            Session::setFlashMessage('error', 'Tekniskt fel. Försök igen eller kontakta en administratör!');
            $this->view->render(self::LOGIN_VIEW);
            return;
        }
        //Fail if passwork did not verify
        if (!$this->passwordService->verifyPassword($providedPassword, $medlem->password)) {
            $this->app->getLogger()->info("Failed login. Incorrect password for member: " . $providedEmail . ' IP: ' . $this->remoteIp);
            Session::setFlashMessage('error', self::BAD_EMAIL_OR_PASSWORD);
            $this->view->render(self::LOGIN_VIEW);
            return;
        }
        // User is successfully logged in, regenerate session id because it's a safe practice
        $this->app->getLogger()->info("Member logged in. Member email: " . $medlem->email .  ' IP: ' . $this->remoteIp);
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
            Session::set('is_admin', false);
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
}
