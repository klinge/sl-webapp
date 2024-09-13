<?php

namespace App\Controllers;

class UserController extends BaseController
{
    public function home()
    {
        //Put everyting in the data variable that is used by the view
        $data = [
            "title" => "Medlemssidan..",
        ];
        $this->render('/user/index', $data);
    }
}
