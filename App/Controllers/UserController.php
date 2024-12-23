<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Application;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;

class UserController extends BaseController
{
    private View $view;

    public function __construct(Application $app, ServerRequestInterface $request)
    {
        parent::__construct($app, $request);
        $this->view = new View($this->app);
    }

    public function home()
    {
        //Put everyting in the data variable that is used by the view
        $data = [
            "title" => "Medlemssidan..",
        ];
        $this->view->render('/user/index', $data);
    }
}
