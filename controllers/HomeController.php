<?php

require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/BaseController.php';

class HomeController extends BaseController
{

  public function index()
  {
    $this->render('/../views/home.php');
  }
}