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
                $this->jsonResponse(['success' => false, 'message' => 'Du måste vara inloggad för åtkomst till denna tjänst.'], 401);

                //Log the exception
                $this->app->getLogger()->warning('Ajax request, user not logged in. URI: ' .
                    $this->request->getUri()->__toString() .
                    ', Remote IP: ' .
                    $this->request->getServerParams()['REMOTE_ADDR']);

                $this->doExit();
            }
        } elseif ($match && !in_array($match['name'], RouteConfig::$noLoginRequiredRoutes) && !Session::get('user_id')) {
            //Log the exception
            $this->app->getLogger()->info('Request to protected page, user not logged in. URI: ' .
                $this->request->getUri()->__toString() .
                ', Remote IP: ' .
                $this->request->getServerParams()['REMOTE_ADDR']);

            // Store the current URL in the session, this is used by AuthController::login() to redirect the user back
            // to the page they wanted after login
            Session::set('redirect_url', $match['name']);
            //Then require login
            Session::setFlashMessage('error', 'Du måste vara inloggad för att se denna sida.');

            //Redirect user to login page with a 401 Unauthorized status code
            http_response_code(401);
            header('Location: ' . $this->app->getRouter()->generate('show-login'));
            $this->doExit();
        }
    }
}
