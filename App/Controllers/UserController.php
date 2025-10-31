<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UrlGeneratorService;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;
use League\Container\Container;

class UserController extends BaseController
{
    private View $view;

    public function __construct(UrlGeneratorService $urlGenerator, ServerRequestInterface $request, Logger $logger, Container $container, View $view)
    {
        parent::__construct($urlGenerator, $request, $logger, $container);
        $this->view = $view;
    }

    public function home(): ResponseInterface
    {
        //Put everyting in the data variable that is used by the view
        $data = [
            "title" => "Medlemssidan..",
        ];
        return $this->view->render('/user/index', $data);
    }
}
