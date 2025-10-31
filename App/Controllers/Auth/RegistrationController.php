<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Services\UrlGeneratorService;
use App\Services\Auth\UserAuthenticationService;
use App\Traits\ResponseFormatter;
use App\Utils\Session;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;
use League\Container\Container;

class RegistrationController extends AuthBaseController
{
    use ResponseFormatter;

    private const REGISTER_VIEW = 'login/viewRegisterAccount';

    private UserAuthenticationService $userAuthService;
    private View $view;

    public function __construct(
        UrlGeneratorService $urlGenerator,
        ServerRequestInterface $request,
        Logger $logger,
        Container $container,
        string $turnstileSecret,
        UserAuthenticationService $userAuthSvc,
        View $view
    ) {
        parent::__construct($urlGenerator, $request, $logger, $container, $turnstileSecret);
        $this->userAuthService = $userAuthSvc;
        $this->view = $view;
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

    public function activate(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $result = $this->userAuthService->activateAccount($params['token']);

        if (!$result['success']) {
            return $this->redirectWithError('login', $result['message']);
        } else {
            return $this->redirectWithSuccess('login', 'Ditt konto är nu aktiverat. Du kan nu logga in.');
        }
    }
}
