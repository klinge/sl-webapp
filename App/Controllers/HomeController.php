<?php

namespace App\Controllers;

require __DIR__ . '/../vendor/autoload.php';

class HomeController extends BaseController
{

  public function index()
  {
    $this->render('/../views/home.php');
  }
}