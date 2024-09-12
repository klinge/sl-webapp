<?php

namespace App\Controllers;

class HomeController extends BaseController
{
    public function index()
    {
        $this->render('home');
    }

    public function pageNotFound()
    {
        $this->render('404');
    }
}
