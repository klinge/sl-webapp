<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Session;

class CsrfMiddleware extends BaseMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        //echo "In CsrfMiddleware. Session token is: " . $_SESSION['csrf_token'];

        if ($this->request['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $token)) {
                $this->app->getLogger()->warning('CSRF token mismatch');
                //Set different responses depending on if it was an ajax request or not
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse(['status' => 'error', 'message' => 'Error validating csrf token'], 401);
                } else {
                    Session::setFlashMessage('error', 'Kunde inte validera CSFR-token..');
                    header('Location: ' . $this->app->getRouter()->generate('tech-error'));
                }
                exit;
            }
        }
        return;
    }
}
