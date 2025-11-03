<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\RollService;
use App\Services\UrlGeneratorService;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class RollController extends BaseController
{
    public function __construct(
        private RollService $rollService,
        private View $view,
        UrlGeneratorService $urlGenerator
    ) {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Lists all roles
     *
     * @return ResponseInterface View response with all roles
     */
    public function list(): ResponseInterface
    {
        $roller = $this->rollService->getAllRoles();
        $data = [
            "title" => "Visa roller",
            "items" => $roller
        ];
        return $this->view->render('viewRoller', $data);
    }

    /**
     * Lists all members that has a specific role.
     *
     * Fetches member data, roles, sailings, and payments for the specified member ID
     * and renders them in an edit view.
     *
     * @param ServerRequestInterface $request The request object
     * @param array<string, mixed> $params The route parameters, must contain the role 'id'
     * @return ResponseInterface View response with members in role or error redirect
     */
    public function membersInRole(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $rollId = (int) $params['id'];
        $result = $this->rollService->getMembersInRole($rollId);
        return $this->jsonResponse($result);
    }
}
