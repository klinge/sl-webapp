<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Application;
use App\Models\MedlemRepository;
use App\Models\Roll;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use Monolog\Logger;

class RollController extends BaseController
{
    private View $view;
    private Roll $roll;
    private MedlemRepository $medlemRepo;

    public function __construct(
        Application $app,
        ServerRequestInterface $request,
        Logger $logger,
        View $view,
        Roll $roll,
        MedlemRepository $medlemRepo
    ) {
        parent::__construct($app, $request, $logger);
        $this->view = $view;
        $this->roll = $roll;
        $this->medlemRepo = $medlemRepo;
    }

    public function list(): void
    {
        $roller = $this->roll->getAll();
        $data = [
            "title" => "Visa roller",
            "items" => $roller
        ];
        $this->view->render('viewRoller', $data);
    }

    public function membersInRole(array $params): void
    {
        $rollId = (int) $params['id'];
        $result = $this->medlemRepo->getMembersByRollId($rollId);
        $this->jsonResponse($result);
    }
}
