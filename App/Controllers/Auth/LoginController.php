<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Application;
use App\Models\MedlemRepository;
use App\Models\Medlem;
use App\Services\Auth\PasswordService;
use App\Traits\ResponseFormatter;
use App\Utils\View;
use App\Utils\Session;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use PDO;
use Monolog\Logger;

class LoginController extends AuthBaseController
{
    use ResponseFormatter;

    private PDO $conn;
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
    public function __construct(
        Application $app,
        ServerRequestInterface $request,
        Logger $logger,
        PDO $conn,
        PasswordService $pwdService
    ) {
        parent::__construct($app, $request, $logger);
        $this->conn = $conn;
        $this->view = new View($this->app);
        $this->passwordService = $pwdService;
        $this->medlemRepo = new MedlemRepository($this->conn, $this->logger);
    }

    /**
     * Renders the login view.
     *
     * This method sets the CSRF token and then renders the login view template.
     */
    public function showLogin(): ResponseInterface
    {
        $this->setCsrfToken();
        return $this->view->render(self::LOGIN_VIEW);
    }

    /**
     * Handles user login process.
     *
     * Validates reCAPTCHA, authenticates user credentials,
     * and manages session upon successful login.
     *
     * @return ResponseInterface
     */
    public function login(): ResponseInterface
    {
        //First validate recaptcha and send user back to login page if failed
        if (!$this->validateRecaptcha()) {
            return $this->renderWithError(self::LOGIN_VIEW, self::RECAPTCHA_ERROR_MESSAGE);
        }

        $providedEmail = $this->request->getParsedBody()['email'] ?? '';
        $providedPassword = $this->request->getParsedBody()['password'] ?? '';

        if (empty($providedEmail) || empty($providedPassword)) {
            $this->logger->info("Failed login. Empty email or password. IP: " . $this->remoteIp);
            return $this->renderWithError(self::LOGIN_VIEW, self::BAD_EMAIL_OR_PASSWORD);
        }

        $result = $this->medlemRepo->getMemberByEmail($providedEmail);

        //User not found
        if (!$result) {
            $this->logger->info("Failed login. Email not existing: " . $providedEmail . ' IP: ' . $this->remoteIp);
            return $this->renderWithError(self::LOGIN_VIEW, self::BAD_EMAIL_OR_PASSWORD);
        }
        //Get medlem object from repository
        $medlem = $this->medlemRepo->getById($result['id']);
        if (!$medlem) {
            $this->logger->error("Technical error. Could not create member object for member id: " . $result['id']);
            return $this->renderWithError(self::LOGIN_VIEW, 'Tekniskt fel. Försök igen eller kontakta en administratör!');
        }
        //Fail if passwork did not verify
        if (!$this->passwordService->verifyPassword($providedPassword, $medlem->password)) {
            $this->logger->info("Failed login. Incorrect password for member: " . $providedEmail . ' IP: ' . $this->remoteIp);
            return $this->renderWithError(self::LOGIN_VIEW, self::BAD_EMAIL_OR_PASSWORD);
        }
        // User is successfully logged in, regenerate session id because it's a safe practice
        $this->logger->info("Member logged in. Member email: " . $medlem->email .  ' IP: ' . $this->remoteIp);
        Session::regenerateId();

        Session::set('user_id', $medlem->id);
        Session::set('fornamn', $medlem->fornamn);

        // Send admins and users to different parts of the site
        if ($medlem->isAdmin) {
            Session::set('is_admin', true);
            //Check if there is a redirect url and if so redirect the user back there otherwise to homepage
            $redirectUrl = Session::get('redirect_url');
            if ($redirectUrl) {
                Session::remove('redirect_url');
                return new \Laminas\Diactoros\Response\RedirectResponse($redirectUrl);
            }
            $route = 'home';
        } else {
            Session::set('is_admin', false);
            //if user is not an admin send them to the user part of the site
            $route = 'user-home';
        }
        Session::remove('redirect_url');
        return $this->redirectWithSuccess($route);
    }

    /**
     * Handles user logout process.
     *
     * Removes user session data and redirects to the login page.
     *
     * @return ResponseInterface
     */
    public function logout(): ResponseInterface
    {
        Session::remove('user_id');
        Session::remove('fornamn');
        Session::destroy();
        return $this->redirectWithSuccess('show-login');
    }
}
