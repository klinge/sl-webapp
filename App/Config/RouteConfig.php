<?php

namespace App\Config;

use League\Route\Router;

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
    public static function createAppRoutes(Router $router)
    {
        $router->get('/', 'App\\Controllers\\HomeController::index')->setName('home');

        $router->get('/medlem', 'App\\Controllers\\MedlemController::listAll')->setName('medlem-list');
        $router->get('/medlem/json', 'App\\Controllers\\MedlemController::listJson')->setName('medlem-list-json');
        $router->get('/medlem/{id:number}', 'App\\Controllers\\MedlemController::edit')->setName('medlem-edit');
        $router->post('/medlem/{id:number}', 'App\\Controllers\\MedlemController::update')->setName('medlem-update');
        $router->get('/medlem/new', 'App\\Controllers\\MedlemController::showNewForm')->setName('medlem-new');
        $router->post('/medlem/new', 'App\\Controllers\\MedlemController::create')->setName('medlem-create');
        $router->post('/medlem/delete', 'App\\Controllers\\MedlemController::delete')->setName('medlem-delete');

        $router->get('/betalning', 'App\\Controllers\\BetalningController::list')->setName('betalning-list');
        $router->get('/betalning/{id:number}', 'App\\Controllers\\BetalningController::getBetalning')->setName('betalning-edit');
        $router->get('/betalning/medlem/{id:number}', 'App\\Controllers\\BetalningController::getMedlemBetalning')->setName('betalning-medlem');
        $router->post('/betalning/create', 'App\\Controllers\\BetalningController::createBetalning')->setName('betalning-create');
        $router->post('/betalning/delete/{id:number}', 'App\\Controllers\\BetalningController::deleteBetalning')->setName('betalning-delete');

        $router->get('/segling', 'App\\Controllers\\SeglingController::list')->setName('segling-list');
        $router->get('/segling/{id:number}', 'App\\Controllers\\SeglingController::edit')->setName('segling-edit');
        $router->post('/segling/{id:number}', 'App\\Controllers\\SeglingController::save')->setName('segling-save');
        $router->get('/segling/new', 'App\\Controllers\\SeglingController::showCreate')->setName('segling-show-create');
        $router->post('/segling/new', 'App\\Controllers\\SeglingController::create')->setName('segling-create');
        $router->post('/segling/delete/{id:number}', 'App\\Controllers\\SeglingController::delete')->setName('segling-delete');
        $router->post('/segling/medlem', 'App\\Controllers\\SeglingController::saveMedlem')->setName('segling-medlem-save');
        $router->post('/segling/medlem/delete', 'App\\Controllers\\SeglingController::deleteMedlemFromSegling')->setName('segling-medlem-delete');

        $router->get('/roller', 'App\\Controllers\\RollController::list')->setName('roll-list');
        $router->get('/roller/{id:number}/medlem', 'App\\Controllers\\RollController::membersInRole')->setName('roll-medlemmar');

        $router->get('/login', 'App\\Controllers\\Auth\\LoginController::showLogin')->setName('show-login');
        $router->post('/login', 'App\\Controllers\\Auth\\LoginController::login')->setName('login');
        $router->get('/logout', 'App\\Controllers\\Auth\\LoginController::logout')->setName('logout');
        $router->get('/auth/register', 'App\\Controllers\\Auth\\RegistrationController::showRegister')->setName('show-register');
        $router->post('/auth/register', 'App\\Controllers\\Auth\\RegistrationController::register')->setName('register');
        $router->get('/auth/register/{token}', 'App\\Controllers\\Auth\\RegistrationController::activate')->setName('register-activate');
        $router->get('/auth/bytlosenord', 'App\\Controllers\\Auth\\PasswordController::showRequestPwd')->setName('show-request-password');
        $router->post('/auth/bytlosenord', 'App\\Controllers\\Auth\\PasswordController::sendPwdRequestToken')->setName('handle-request-password');
        $router->get('/auth/bytlosenord/{token}', 'App\\Controllers\\Auth\\PasswordController::showResetPassword')->setName('show-reset-password');
        $router->post('/auth/sparalosenord', 'App\\Controllers\\Auth\\PasswordController::resetAndSavePassword')->setName('reset-password');

        $router->get('/reports', 'App\\Controllers\\ReportController::show')->setName('show-report-page');
        $router->post('/reports/payments', 'App\\Controllers\\ReportController::showPaymentReport')->setName('report-payment');
        $router->get('/reports/member-emails', 'App\\Controllers\\ReportController::showMemberEmails')->setName('report-member-emails');

        $router->get('/user', 'App\\Controllers\\UserController::home')->setName('user-home');

        $router->post('/webhooks/git/handle', 'App\\Controllers\\WebhookController::handle')->setName('git-webhook-listener');

        $router->get('/error', 'App\\Controllers\\HomeController::technicalError')->setName('tech-error');

        // Catch-all route for 404
        $router->map('GET', '/{path:.*}', 'App\\Controllers\\HomeController::pageNotFound')->setName('404');
    }
}
