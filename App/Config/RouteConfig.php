<?php

namespace App\Config;

use League\Route\Router;
use App\Middleware\RequireAdminMiddleware;
use App\Middleware\RequireAuthenticationMiddleware;

class RouteConfig
{
    // Central place to put all the applications routes
    public static function createAppRoutes(Router $router)
    {
        $router->get('/', 'App\\Controllers\\HomeController::index')->setName('home');

        $router->group('/medlem', function (\League\Route\RouteGroup $route) {
            $route->map('GET', '/', 'App\\Controllers\\MedlemController::listAll')
                ->setName('medlem-list');
            $route->map('GET', '/json', 'App\\Controllers\\MedlemController::listJson')
                ->setName('medlem-list-json');
            $route->map('GET', '/{id:number}', 'App\\Controllers\\MedlemController::edit')
                ->setName('medlem-edit');
            $route->map('POST', '/{id:number}', 'App\\Controllers\\MedlemController::update')
                ->setName('medlem-update');
            $route->map('GET', '/new', 'App\\Controllers\\MedlemController::showNewForm')
                ->setName('medlem-new');
            $route->map('POST', '/new', 'App\\Controllers\\MedlemController::create')
                ->setName('medlem-create');
            $route->map('POST', '/delete', 'App\\Controllers\\MedlemController::delete')
                ->setName('medlem-delete');
        })->middlewares([new RequireAdminMiddleware()]);

        $router->group('/betalning', function (\League\Route\RouteGroup $route) {
            $route->map('GET', '/', 'App\\Controllers\\BetalningController::list')
                ->setName('betalning-list');
            $route->map('GET', '/{id:number}', 'App\\Controllers\\BetalningController::getBetalning')
                ->setName('betalning-edit');
            $route->map('GET', '/medlem/{id:number}', 'App\\Controllers\\BetalningController::getMedlemBetalning')
                ->setName('betalning-medlem');
            $route->map('POST', '/create', 'App\\Controllers\\BetalningController::createBetalning')
                ->setName('betalning-create');
            $route->map('POST', '/delete/{id:number}', 'App\\Controllers\\BetalningController::deleteBetalning')
                ->setName('betalning-delete');
        })->middlewares([new RequireAdminMiddleware()]);

        $router->group('/segling', function (\League\Route\RouteGroup $route) {
            $route->map('GET', '/', 'App\\Controllers\\SeglingController::list')
                ->setName('segling-list');
            $route->map('GET', '/{id:number}', 'App\\Controllers\\SeglingController::edit')
                ->setName('segling-edit');
            $route->map('POST', '/{id:number}', 'App\\Controllers\\SeglingController::save')
                ->setName('segling-save');
            $route->map('GET', '/new', 'App\\Controllers\\SeglingController::showCreate')
                ->setName('segling-show-create');
            $route->map('POST', '/new', 'App\\Controllers\\SeglingController::create')
                ->setName('segling-create');
            $route->map('POST', '/delete/{id:number}', 'App\\Controllers\\SeglingController::delete')
                ->setName('segling-delete');
            $route->map('POST', '/medlem', 'App\\Controllers\\SeglingController::saveMedlem')
                ->setName('segling-medlem-save');
            $route->map('POST', '/medlem/delete', 'App\\Controllers\\SeglingController::deleteMedlemFromSegling')
                ->setName('segling-medlem-delete');
        })->middlewares([new RequireAdminMiddleware()]);


        $router->group('/roller', function (\League\Route\RouteGroup $route) {
            $route->map('GET', '/', 'App\\Controllers\\RollController::list')
                ->setName('roll-list');
            $route->map('GET', '/{id:number}/medlem', 'App\\Controllers\\RollController::membersInRole')
                ->setName('roll-medlemmar');
        })->middlewares([new RequireAdminMiddleware()]);

        $router->group('/auth', function (\League\Route\RouteGroup $route) {
            $route->map('GET', '/register', 'App\\Controllers\\Auth\\RegistrationController::showRegister')
                ->setName('show-register');
            $route->map('POST', '/register', 'App\\Controllers\\Auth\\RegistrationController::register')
                ->setName('register');
            $route->map('GET', '/register/{token}', 'App\\Controllers\\Auth\\RegistrationController::activate')
                ->setName('register-activate');
            $route->map('GET', '/bytlosenord', 'App\\Controllers\\Auth\\PasswordController::showRequestPwd')
                ->setName('show-request-password');
            $route->map('POST', '/bytlosenord', 'App\\Controllers\\Auth\\PasswordController::sendPwdRequestToken')
                ->setName('handle-request-password');
            $route->map('GET', '/bytlosenord/{token}', 'App\\Controllers\\Auth\\PasswordController::showResetPassword')
                ->setName('show-reset-password');
            $route->map('POST', '/sparalosenord', 'App\\Controllers\\Auth\\PasswordController::resetAndSavePassword')
                ->setName('reset-password');
        });

        $router->get('/login', 'App\\Controllers\\Auth\\LoginController::showLogin')->setName('show-login');
        $router->post('/login', 'App\\Controllers\\Auth\\LoginController::login')->setName('login');
        $router->get('/logout', 'App\\Controllers\\Auth\\LoginController::logout')->setName('logout');

        $router->group('/reports', function (\League\Route\RouteGroup $route) {
            $route->map('GET', '/', 'App\\Controllers\\ReportController::show')
                ->setName('show-report-page');
            $route->map('POST', '/payments', 'App\\Controllers\\ReportController::showPaymentReport')
                ->setName('report-payment');
            $route->map('GET', '/member-emails', 'App\\Controllers\\ReportController::showMemberEmails')
                ->setName('report-member-emails');
        })->middlewares([new RequireAdminMiddleware()]);

        $router->group('/reports', function (\League\Route\RouteGroup $route) {
            $route->map('GET', '/', 'App\\Controllers\\UserController::home')
                ->setName('user-home');
        })->middlewares([new RequireAuthenticationMiddleware()]);

        $router->post('/webhooks/git/handle', 'App\\Controllers\\WebhookController::handle')->setName('git-webhook-listener');

        $router->get('/error', 'App\\Controllers\\HomeController::technicalError')->setName('tech-error');
    }
}
