<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Application;
use App\Services\Auth\UserAuthenticationService;
use App\Traits\ResponseFormatter;
use App\Utils\Session;
use App\Utils\View;
use App\Utils\Email;
use Psr\Http\Message\ServerRequestInterface;

class RegistrationController extends AuthBaseController
{
    use ResponseFormatter;

    private const REGISTER_VIEW = 'login/viewRegisterAccount';

    private UserAuthenticationService $userAuthService;
    private View $view;

    public function __construct(Application $app, ServerRequestInterface $request)
    {
        parent::__construct($app, $request);
        $this->userAuthService = new UserAuthenticationService($this->conn, $app, new Email($app));
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
            $this->renderWithError(self::REGISTER_VIEW, self::RECAPTCHA_ERROR_MESSAGE);
            return;
        }

        $result = $this->userAuthService->registerUser($this->request->getParsedBody());

        if (!$result['success']) {
            $this->redirectWithError('show-register', $result['message']);
            return;
        }

        Session::setFlashMessage('success', 'E-post med verifieringslänk har skickats till din e-postadress.');
        $this->view->render('login/viewLogin');
    }

    public function activate(array $params): void
    {
        $result = $this->userAuthService->activateAccount($params['token']);

        if (!$result['success']) {
            $this->redirectWithError('login', $result['message']);
        } else {
            $this->redirectWithSuccess('login', 'Ditt konto är nu aktiverat. Du kan nu logga in.');
        }
    }
}
