<?php

namespace App\Config;

class RouteConfig
{
    public static $noLoginRequiredRoutes = [
        'show-login',
        'login',
        'register',
        'register-activate',
        'show-request-password',
        'handle-request-password',
        'show-reset-password',
        'reset-password',
        '404',
        'home'
    ];
}
