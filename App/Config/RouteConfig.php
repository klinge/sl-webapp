<?php

namespace App\Config;

use AltoRouter;

class RouteConfig
{
    //Used by middleware to know what pages does not require login
    public static $noLoginRequiredRoutes = [
        'show-login',
        'show-register',
        'login',
        'logout',
        'register',
        'register-activate',
        'show-request-password',
        'handle-request-password',
        'show-reset-password',
        'reset-password',
        '404',
        'home',
        'git-webhook-listener'
    ];

    // Central place to put all the applications routes
    public static function createAppRoutes(AltoRouter $router)
    {
        $router->map('GET', '/', 'HomeController#index', 'home');

        $router->map('GET', '/medlem', 'MedlemController#listAll', 'medlem-list');
        $router->map('GET', '/medlem/json', 'MedlemController#listJson', 'medlem-list-json');
        $router->map('GET', '/medlem/[i:id]', 'MedlemController#edit', 'medlem-edit');
        $router->map('POST', '/medlem/[i:id]', 'MedlemController#update', 'medlem-update');
        $router->map('GET', '/medlem/new', 'MedlemController#showNewForm', 'medlem-new');
        $router->map('POST', '/medlem/new', 'MedlemController#create', 'medlem-create');
        $router->map('POST', '/medlem/delete', 'MedlemController#delete', 'medlem-delete');

        $router->map('GET', '/betalning', 'BetalningController#list', 'betalning-list');
        $router->map('GET', '/betalning/[i:id]', 'BetalningController#getBetalning', 'betalning-edit');
        $router->map('GET', '/betalning/medlem/[i:id]', 'BetalningController#getMedlemBetalning', 'betalning-medlem');
        $router->map('POST', '/betalning/create', 'BetalningController#createBetalning', 'betalning-create');
        $router->map('POST', '/betalning/delete/[i:id]', 'BetalningController#deleteBetalning', 'betalning-delete');

        $router->map('GET', '/segling', 'SeglingController#list', 'segling-list');
        $router->map('GET', '/segling/[i:id]', 'SeglingController#edit', 'segling-edit');
        $router->map('POST', '/segling/[i:id]', 'SeglingController#save', 'segling-save');
        $router->map('GET', '/segling/new', 'SeglingController#showCreate', 'segling-show-create');
        $router->map('POST', '/segling/new', 'SeglingController#create', 'segling-create');
        $router->map('POST', '/segling/delete/[i:id]', 'SeglingController#delete', 'segling-delete');
        $router->map('POST', '/segling/medlem', 'SeglingController#saveMedlem', 'segling-medlem-save');
        $router->map('POST', '/segling/medlem/delete', 'SeglingController#deleteMedlemFromSegling', 'segling-medlem-delete');

        $router->map('GET', '/roller', 'RollController#list', 'roll-list');
        $router->map('GET', '/roller/[i:id]/medlem', 'RollController#membersInRole', 'roll-medlemmar');

        $router->map('GET', '/login', 'Auth\LoginController#showLogin', 'show-login');
        $router->map('POST', '/login', 'Auth\LoginController#login', 'login');
        $router->map('GET', '/logout', 'Auth\LoginController#logout', 'logout');
        $router->map('GET', '/auth/register', 'Auth\RegistrationController#showRegister', 'show-register');
        $router->map('POST', '/auth/register', 'Auth\RegistrationController#register', 'register');
        $router->map('GET', '/auth/register/[a:token]', 'Auth\RegistrationController#activate', 'register-activate');
        $router->map('GET', '/auth/bytlosenord', 'Auth\PasswordController#showRequestPwd', 'show-request-password');
        $router->map('POST', '/auth/bytlosenord', 'Auth\PasswordController#sendPwdRequestToken', 'handle-request-password');
        $router->map('GET', '/auth/bytlosenord/[a:token]', 'Auth\PasswordController#showResetPassword', 'show-reset-password');
        $router->map('POST', '/auth/sparalosenord', 'Auth\PasswordController#resetAndSavePassword', 'reset-password');

        $router->map('GET', '/reports', 'ReportController#show', 'show-report-page');
        $router->map('POST', '/reports/payments', 'ReportController#showPaymentReport', 'report-payment');
        $router->map('GET', '/reports/member-emails', 'ReportController#showMemberEmails', 'report-member-emails');

        $router->map('GET', '/user', 'UserController#home', 'user-home');

        $router->map('POST', '/webhooks/git/handle', 'WebhookController#handle', 'git-webhook-listener');

        $router->map('GET', '/error', 'HomeController#technicalError', 'tech-error');
        //Route all other urls to 404
        $router->map('GET|POST', '*', 'HomeController#pageNotFound', '404');
    }
}
