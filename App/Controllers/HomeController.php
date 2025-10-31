<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UrlGeneratorService;
use App\Utils\Session;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;
use League\Container\Container;

class HomeController extends BaseController
{
    private View $view;

    public function __construct(UrlGeneratorService $urlGenerator, ServerRequestInterface $request, Logger $logger, Container $container, View $view)
    {
        parent::__construct($urlGenerator, $request, $logger, $container);
        $this->view = $view;
    }

    public function index(): ResponseInterface
    {
        //if user is not logged in show login page, if user is logged in redirect to admin or user homepage
        if (!Session::isLoggedIn()) {
            return $this->view->render('login/viewLogin');
        } elseif (Session::isAdmin()) {
            return $this->view->render('home');
        } else {
            return $this->view->render('user/index');
        }
    }

    public function pageNotFound(): ResponseInterface
    {
        return $this->view->render('404');
    }

    public function technicalError(): ResponseInterface
    {
        return $this->view->render('viewTechnicalError');
    }
}
