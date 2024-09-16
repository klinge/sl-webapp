<?php

namespace App\Middleware;

use App\Utils\Session;
use App\Application;
use App\Config\RouteConfig;

class AuthenticationMiddleware implements MiddlewareInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(): void
    {
        $match = $this->app->getRouter()->match();

        //if it's a valid url AND it's not in the exempt list for login AND the user is not logged in
        if ($match && !in_array($match['name'], RouteConfig::$noLoginRequiredRoutes) && !Session::get('user_id')) {
            // Store the current URL in the session, this is used by AuthController::login() to redirect the user back
            // to the page they wanted after login
            Session::set('redirect_url', $_SERVER['REQUEST_URI']);
            //Then require login
            Session::setFlashMessage('error', 'Du måste vara inloggad för att se denna sida.');
            header('Location: ' . $this->app->getRouter()->generate('show-login'));
            exit;
        }
    }
}
