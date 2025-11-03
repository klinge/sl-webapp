<?php

declare(strict_types=1);

namespace App\Controllers;

use Exception;
use App\Services\BetalningService;
use App\Services\UrlGeneratorService;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;

class BetalningController extends BaseController
{
    public function __construct(
        private BetalningService $betalningService,
        private View $view,
        UrlGeneratorService $urlGenerator
    ) {
        $this->urlGenerator = $urlGenerator;
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

    /**
     * Gets a specific payment by ID.
     *
     * @param ServerRequestInterface $request The HTTP request
     * @param array<string, mixed> $params Route parameters containing 'id'
     * @return ResponseInterface JSON response with payment data or error
     */
    public function getBetalning(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $id = (int) $params['id'];
        // TODO: Implement payment editing functionality
        return $this->jsonResponse(['message' => 'Payment editing not yet implemented']);
    }

    /**
     * Gets all payments for a specific member.
     *
     * @param ServerRequestInterface $request The HTTP request
     * @param array<string, mixed> $params Route parameters containing member 'id'
     * @return ResponseInterface View response with member payments or error message
     */
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

    /**
     * Deletes a payment by ID.
     *
     * @param ServerRequestInterface $request The HTTP request
     * @param array<string, mixed> $params Route parameters containing payment 'id'
     * @return ResponseInterface JSON response with success status and message
     */
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
