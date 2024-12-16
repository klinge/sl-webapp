<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Application;
use App\Models\MedlemRepository;
use App\Models\Roll;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use PDO;
use Monolog\Logger;

class RollController extends BaseController
{
    private View $view;
    private PDO $conn;

    public function __construct(Application $app, ServerRequestInterface $request, Logger $logger, PDO $conn)
    {
        parent::__construct($app, $request, $logger);
        $this->conn = $conn;
        $this->view = new View($this->app);
    }

    public function list()
    {
        $roll = new Roll($this->conn);
        $roller = $roll->getAll();
        $data = [
            "title" => "Visa roller",
            "items" => $roller
        ];
        $this->view->render('viewRoller', $data);
        return;
    }

    public function membersInRole(array $params)
    {
        $rollId = (int) $params['id'];
        $medlemRepo = new MedlemRepository($this->conn, $this->logger);
        $result = $medlemRepo->getMembersByRollId($rollId);
        $this->jsonResponse($result);
        exit;
    }
}
