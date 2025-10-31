<?php

declare(strict_types=1);

namespace App\Controllers;

use Exception;
use App\Services\BetalningService;
use App\Utils\View;
use App\Application;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;

class BetalningController extends BaseController
{
    private View $view;
    private BetalningService $betalningService;

    public function __construct(
        Application $app,
        ServerRequestInterface $request,
        Logger $logger,
        BetalningService $betalningService
    ) {
        parent::__construct($app, $request, $logger);
        $this->view = new View($this->app);
        $this->betalningService = $betalningService;
    }

    public function list(): ResponseInterface
    {
        $payments = $this->betalningService->getAllPayments();

        $data = [
            "title" => "Betalningslista",
            "items" => $payments
        ];
        return $this->view->render('viewBetalning', $data);
    }

    public function getBetalning(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $id = (int) $params['id'];
        // TODO: Implement payment editing functionality
        return $this->jsonResponse(['message' => 'Payment editing not yet implemented']);
    }

    public function getMedlemBetalning(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $id = (int) $params['id'];

        try {
            $memberData = $this->betalningService->getPaymentsForMember($id);
            $medlem = $memberData['medlem'];
            $payments = $memberData['payments'];

            if (!empty($payments)) {
                $data = [
                    "success" => true,
                    "title" => "Betalningar för: " . $medlem->getNamn(),
                    "items" => $payments
                ];
            } else {
                $data = [
                    "success" => false,
                    "title" => "Inga betalningar hittades"
                ];
            }
        } catch (Exception $e) {
            $data = [
                'success' => false,
                'title' => 'Medlem hittades inte'
            ];
        }

        return $this->view->render('viewBetalning', $data);
    }
    public function createBetalning(ServerRequestInterface $request): ResponseInterface
    {
        $postData = $this->request->getParsedBody();
        $result = $this->betalningService->createPayment($postData);

        return $this->jsonResponse([
            'success' => $result->success,
            'message' => $result->message
        ]);
    }

    public function deleteBetalning(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $id = (int) $params['id'];
        $result = $this->betalningService->deletePayment($id);

        return $this->jsonResponse([
            'success' => $result->success,
            'message' => $result->message
        ]);
    }
}
