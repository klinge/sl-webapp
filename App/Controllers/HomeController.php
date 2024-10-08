<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Application;
use App\Utils\Session;
use App\Utils\View;

class HomeController
{
    private Application $app;
    private View $view;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->view = new View($this->app);
    }

    public function index()
    {
        $this->view->render('home');
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
