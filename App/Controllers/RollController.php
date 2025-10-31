<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\RollService;
use App\Utils\View;
use App\Application;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class RollController extends BaseController
{
    public function __construct(
        private RollService $rollService,
        private View $view,
        Application $app
    ) {
        $this->app = $app;
    }

    public function list(): ResponseInterface
    {
        $roller = $this->rollService->getAllRoles();
        $data = [
            "title" => "Visa roller",
            "items" => $roller
        ];
        return $this->view->render('viewRoller', $data);
    }

    public function membersInRole(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $rollId = (int) $params['id'];
        $result = $this->rollService->getMembersInRole($rollId);
        return $this->jsonResponse($result);
    }
}
