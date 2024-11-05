<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Application;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;

class ReportController extends BaseController
{
    private View $view;

    public function __construct(Application $app, ServerRequestInterface $request)
    {
        parent::__construct($app, $request);
        $this->view = new View($this->app);
    }

    public function show()
    {
        $data = [
            "title" => "Rapporter",
        ];
        $this->view->render('viewRapporter', $data);
        return;
    }
}
