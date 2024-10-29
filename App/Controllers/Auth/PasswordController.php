<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Application;
use App\Services\Auth\UserAuthenticationService;
use App\Traits\ResponseFormatter;
use App\Utils\View;
use App\Utils\Email;
use App\Utils\Session;
use Psr\Http\Message\ServerRequestInterface;

class PasswordController extends AuthBaseController
{
    use ResponseFormatter;

    private const NEWPASSWORD_VIEW = 'login/viewReqPassword';
    private const RESET_PASSWORD_VIEW = 'login/viewSetNewPassword';
    private UserAuthenticationService $authService;
    private View $view;

    public function __construct(Application $app, ServerRequestInterface $request)
    {
        parent::__construct($app, $request);
        $this->authService = new UserAuthenticationService($this->conn, $app, new Email($app));
        $this->view = new View($this->app);
    }

    public function showRequestPwd(): void
    {
        $this->view->render(self::NEWPASSWORD_VIEW);
    }

    public function sendPwdRequestToken(): void
    {
        if (!$this->validateRecaptcha()) {
            $this->renderWithError(self::NEWPASSWORD_VIEW, self::RECAPTCHA_ERROR_MESSAGE);
            return;
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

        $this->view->render(self::NEWPASSWORD_VIEW);
    }

    public function showResetPassword(array $params): void
    {
        $result = $this->authService->validateResetToken($params['token']);

        if ($result['success']) {
            $viewData = [
                'email' => $result['email'],
                'token' => $params['token']
            ];
            $this->setCsrfToken();
            $this->view->render(self::RESET_PASSWORD_VIEW, $viewData);
            return;
        }

        $this->redirectWithError('show-request-password', $result['message']);
    }

    public function resetAndSavePassword(): void
    {
        $formData = $this->request->getParsedBody();
        $result = $this->authService->resetPassword($formData);

        if (!$result['success']) {
            Session::setFlashMessage('error', $result['message']);
            $viewData = [
                'email' => $formData['email'],
                'token' => $formData['token']
            ];
            $this->view->render(self::RESET_PASSWORD_VIEW, $viewData);
            return;
        }

        $this->redirectWithSuccess('login', 'Ditt lösenord är uppdaterat. Du kan nu logga in med ditt nya lösenord.');
    }
}
