<?php

namespace App\Middleware;

use App\Utils\Session;
use App\Application;

class AuthMiddleware
{
    private $app;
    private $exemptRoutes = [
        'show-login',
        'login',
        'register',
        'register-activate',
        'show-request-password',
        'handle-request-password',
        'show-reset-password',
        'reset-password'
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle()
    {
        $match = $this->app->getRouter()->match();

        if ($match && !in_array($match['name'], $this->exemptRoutes) && !Session::get('user_id')) {
            Session::setFlashMessage('error', 'Du måste vara inloggad för att se denna sida.');
            header('Location: ' . $this->app->getRouter()->generate('show-login'));
            exit;
        }
    }
}
