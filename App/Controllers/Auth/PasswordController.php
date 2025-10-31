<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Services\UrlGeneratorService;
use App\Services\Auth\UserAuthenticationService;
use App\Traits\ResponseFormatter;
use App\Utils\View;
use App\Utils\Session;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;
use League\Container\Container;

class PasswordController extends AuthBaseController
{
    use ResponseFormatter;

    private const NEWPASSWORD_VIEW = 'login/viewReqPassword';
    private const RESET_PASSWORD_VIEW = 'login/viewSetNewPassword';
    private UserAuthenticationService $authService;
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
        $this->authService = $userAuthSvc;
        $this->view = $view;
    }

    public function showRequestPwd(): ResponseInterface
    {
        return $this->view->render(self::NEWPASSWORD_VIEW);
    }

    public function sendPwdRequestToken(): ResponseInterface
    {
        if (!$this->validateRecaptcha()) {
            return $this->renderWithError(self::NEWPASSWORD_VIEW, self::RECAPTCHA_ERROR_MESSAGE);
        }

        $email = $this->request->getParsedBody()['email'] ?? '';
        $result = $this->authService->requestPasswordReset($email);
        if ($result['success']) {
            Session::setFlashMessage(
                'success',
                'Om du har ett konto får du strax ett mail med en återställningslänk till din e-postadress.'
            );
        } else {
            Session::setFlashMessage('error', 'Kunde inte skicka mail för lösenordsåterställning. Försök igen.');
        }

        return $this->view->render(self::NEWPASSWORD_VIEW);
    }

    public function showResetPassword(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $result = $this->authService->validateResetToken($params['token']);

        if ($result['success']) {
            $viewData = [
                'email' => $result['email'],
                'token' => $params['token']
            ];
            $this->setCsrfToken();
            return $this->view->render(self::RESET_PASSWORD_VIEW, $viewData);
        }

        return $this->redirectWithError('show-request-password', $result['message']);
    }

    public function resetAndSavePassword(): ResponseInterface
    {
        $formData = $this->request->getParsedBody();
        $result = $this->authService->resetPassword($formData);

        if (!$result['success']) {
            Session::setFlashMessage('error', $result['message']);
            $viewData = [
                'email' => $formData['email'],
                'token' => $formData['token']
            ];
            return $this->view->render(self::RESET_PASSWORD_VIEW, $viewData);
        }

        return $this->redirectWithSuccess('login', 'Ditt lösenord är uppdaterat. Du kan nu logga in med ditt nya lösenord.');
    }
}
