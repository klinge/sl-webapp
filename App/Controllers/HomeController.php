<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Application;
use App\Utils\Session;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;

class HomeController extends BaseController
{
    private View $view;

    public function __construct(Application $app, ServerRequestInterface $request)
    {
        parent::__construct($app, $request);
        $this->view = new View($this->app);
    }

    public function index(): void
    {
        //if user is not logged in show login page, if user is logged in redirect to admin or user homepage
        if (!Session::isLoggedIn()) {
            $this->view->render('login/viewLogin');
        } elseif (Session::isAdmin()) {
            $this->view->render('home');
        } else {
            $this->view->render('user/index');
        }
    }

    public function pageNotFound()
    {
        $this->view->render('404');
    }

    public function technicalError()
    {
        $this->view->render('viewTechnicalError');
    }
}
