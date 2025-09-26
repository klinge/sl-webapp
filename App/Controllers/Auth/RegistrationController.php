<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Application;
use App\Services\Auth\UserAuthenticationService;
use App\Traits\ResponseFormatter;
use App\Utils\Session;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;

class RegistrationController extends AuthBaseController
{
    use ResponseFormatter;

    private const REGISTER_VIEW = 'login/viewRegisterAccount';

    private UserAuthenticationService $userAuthService;
    private View $view;

    public function __construct(Application $app, ServerRequestInterface $request, Logger $logger, UserAuthenticationService $userAuthSvc)
    {
        parent::__construct($app, $request, $logger);
        $this->userAuthService = $userAuthSvc;
        $this->view = new View($this->app);
    }

    public function showRegister(): ResponseInterface
    {
        $this->setCsrfToken();
        return $this->view->render(self::REGISTER_VIEW);
    }

    public function register(): ResponseInterface
    {
        if (!$this->validateRecaptcha()) {
            return $this->renderWithError(self::REGISTER_VIEW, self::RECAPTCHA_ERROR_MESSAGE);
        }

        $result = $this->userAuthService->registerUser($this->request->getParsedBody());

        if (!$result['success']) {
            return $this->redirectWithError('show-register', $result['message']);
        }

        Session::setFlashMessage('success', 'E-post med verifieringslänk har skickats till din e-postadress.');
        return $this->view->render('login/viewLogin');
    }

    public function activate(array $params): ResponseInterface
    {
        $result = $this->userAuthService->activateAccount($params['token']);

        if (!$result['success']) {
            return $this->redirectWithError('login', $result['message']);
        } else {
            return $this->redirectWithSuccess('login', 'Ditt konto är nu aktiverat. Du kan nu logga in.');
        }
    }
}
