<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\RouteConfig;
use App\Utils\Session;

class AuthenticationMiddleware extends BaseMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        $match = $this->app->getRouter()->match();

        // Start by handling api/ajax requests, user does always have to be logged in
        if ($this->isAjaxRequest()) {
            if (!Session::get('user_id')) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Du måste vara inloggad för att åtkomst till denna tjänst.'], 200);
            }
        } elseif ($match && !in_array($match['name'], RouteConfig::$noLoginRequiredRoutes) && !Session::get('user_id')) {
            // Store the current URL in the session, this is used by AuthController::login() to redirect the user back
            // to the page they wanted after login
            Session::set('redirect_url', $this->request->getUri()->__toString());
            //Then require login
            Session::setFlashMessage('error', 'Du måste vara inloggad för att se denna sida.');
            header('Location: ' . $this->app->getRouter()->generate('show-login'));
            exit;
        }
    }
}
