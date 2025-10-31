<?php

declare(strict_types=1);

namespace App\Controllers;

use Exception;
use App\Services\SeglingService;
use App\Services\UrlGeneratorService;
use App\Utils\View;
use App\Utils\Session;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Monolog\Logger;

class SeglingController extends BaseController
{
    public function __construct(
        private SeglingService $seglingService,
        private View $view,
        UrlGeneratorService $urlGenerator
    ) {
        $this->urlGenerator = $urlGenerator;
    }

    public function list(): ResponseInterface
    {
        $seglingar = $this->seglingService->getAllSeglingar();

        $data = [
            "title" => "Bokningslista",
            "newAction" => $this->createUrl('segling-show-create'),
            "items" => $seglingar
        ];
        return $this->view->render('viewSegling', $data);
    }

    public function edit(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $id = (int) $params['id'];
        $formAction = $this->createUrl('segling-save', ['id' => $id]);

        try {
            $editData = $this->seglingService->getSeglingEditData($id);

            $data = [
                "title" => "Visa segling",
                "items" => $editData['segling'],
                "roles" => $editData['roles'],
                "allaSkeppare" => $editData['allaSkeppare'],
                "allaBatsman" => $editData['allaBatsman'],
                "allaKockar" => $editData['allaKockar'],
                "formUrl" => $formAction
            ];
            return $this->view->render('viewSeglingEdit', $data);
        } catch (Exception $e) {
            return $this->notFoundResponse();
        }
    }

    public function save(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $id = (int) $params['id'];
        $postData = $this->request->getParsedBody();
        $result = $this->seglingService->updateSegling($id, $postData);

        if ($result->success) {
            Session::setFlashMessage('success', $result->message);
            $redirectUrl = $this->createUrl($result->redirectRoute);
            return new RedirectResponse($redirectUrl);
        } else {
            return $this->jsonResponse(['success' => false, 'message' => $result->message]);
        }
    }

    public function delete(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $id = (int) $params['id'];
        $result = $this->seglingService->deleteSegling($id);

        Session::setFlashMessage($result->success ? 'success' : 'error', $result->message);
        $redirectUrl = $this->createUrl($result->redirectRoute);
        return new RedirectResponse($redirectUrl);
    }

    public function showCreate(): ResponseInterface
    {
        $formAction = $this->createUrl('segling-create');
        $data = [
            "title" => "Skapa ny segling",
            "formUrl" => $formAction
        ];
        return $this->view->render('viewSeglingNew', $data);
    }

    public function create(): ResponseInterface
    {
        $postData = $this->request->getParsedBody();
        $result = $this->seglingService->createSegling($postData);

        Session::setFlashMessage($result->success ? 'success' : 'error', $result->message);

        if ($result->success && $result->seglingId) {
            $redirectUrl = $this->createUrl($result->redirectRoute, ['id' => $result->seglingId]);
        } else {
            $redirectUrl = $this->createUrl($result->redirectRoute);
        }

        return new RedirectResponse($redirectUrl);
    }

    public function saveMedlem(): ResponseInterface
    {
        $postData = $this->request->getParsedBody();
        $result = $this->seglingService->addMemberToSegling($postData);

        return $this->jsonResponse([
            'success' => $result->success,
            'message' => $result->message
        ]);
    }

    public function deleteMedlemFromSegling(): ResponseInterface
    {
        // Handle JSON request body
        $contentType = $this->request->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $body = $this->request->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $json = $body->getContents();
            $data = json_decode($json, true);
        } else {
            $data = $this->request->getParsedBody();
        }

        $result = $this->seglingService->removeMemberFromSegling($data);

        return $this->jsonResponse([
            'status' => $result->success ? 'ok' : 'fail',
            'error' => $result->success ? null : $result->message
        ]);
    }
}
