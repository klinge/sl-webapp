<?php

declare(strict_types=1);

namespace App\Controllers;

class HomeController
{
    public function index()
    {
        $this->render('home');
    }

    public function pageNotFound()
    {
        $this->render('404');
    }

    protected function render(string $viewName): void
    {
        require $_SERVER['DOCUMENT_ROOT'] . "/sl-webapp/views/" . $viewName . ".php";
    }
}
