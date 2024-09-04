<?php

namespace App\Controllers;

class HomeController extends BaseController
{

  public function index()
  {
    $this->render('home');
  }

  public function PageNotFound()
  {
    $this->render('404');
  }
}
