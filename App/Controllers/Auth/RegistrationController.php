<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Application;
use App\Services\Auth\UserAuthenticationService;
use App\Utils\Session;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;

class RegistrationController extends AuthBaseController
{
    private const REGISTER_VIEW = 'login/viewRegisterAccount';

    private UserAuthenticationService $userAuthService;
    private View $view;

    public function __construct(Application $app, ServerRequestInterface $request)
    {
        parent::__construct($app, $request);
        $this->userAuthService = new UserAuthenticationService($this->conn, $app);
        $this->view = new View($this->app);
    }

    public function showRegister(): void
    {
        $this->setCsrfToken();
        $this->view->render(self::REGISTER_VIEW);
    }

    public function register(): void
    {
        if (!$this->validateRecaptcha()) {
            Session::setFlashMessage('error', self::RECAPTCHA_ERROR_MESSAGE);
            $this->view->render(self::REGISTER_VIEW);
            return;
        }

        $result = $this->userAuthService->registerUser($this->request->getParsedBody());

        if (!$result['success']) {
            Session::setFlashMessage('error', $result['message']);
            $this->view->render(self::REGISTER_VIEW);
            return;
        }

        Session::setFlashMessage('success', 'E-post med verifieringslänk har skickats till din e-postadress.');
        $this->view->render('login/viewLogin');
    }

    public function activate(array $params): void
    {
        $result = $this->userAuthService->activateAccount($params['token']);

        if (!$result['success']) {
            Session::setFlashMessage('error', $result['message']);
        } else {
            Session::setFlashMessage('success', 'Ditt konto är nu aktiverat. Du kan nu logga in.');
        }
        header('Location: ' . $this->app->getRouter()->generate('login'));
    }
}
