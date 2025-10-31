<?php

declare(strict_types=1);

namespace App\Controllers;

use Exception;
use App\Services\MedlemService;
use App\Services\UrlGeneratorService;
use App\Utils\View;
use App\Traits\ResponseFormatter;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;

/**
 * MedlemController handles operations related to members (medlemmar).
 *
 * This controller manages CRUD operations for members, including listing,
 * editing, creating, and deleting member records. It also handles related
 * operations such as managing roles and payments for members.
 */
class MedlemController extends BaseController
{
    use ResponseFormatter;



    public function __construct(
        private MedlemService $medlemService,
        private View $view,
        UrlGeneratorService $urlGenerator
    ) {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Lists all members.
     *
     * Fetches all members from the repository and renders them in a view.
     */
    public function listAll(): ResponseInterface
    {
        $result = $this->medlemService->getAllMembers();

        $data = [
            "title" => "Medlemmar",
            "items" => $result,
            'newAction' => $this->createUrl('medlem-new')
        ];
        return $this->view->render('viewMedlem', $data);
    }

    public function listJson(): ResponseInterface
    {
        $result = $this->medlemService->getAllMembers();
        return $this->jsonResponse($result);
    }

    /**
     * Edits a specific member.
     *
     * Fetches member data, roles, sailings, and payments for the specified member ID
     * and renders them in an edit view.
     *
     * @param ServerRequestInterface $request The request object
     * @param array $params The route parameters, must contain 'id'
     */
    public function edit(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $id = (int) $params['id'];

        try {
            $memberData = $this->medlemService->getMemberEditData($id);

            $data = [
                "title" => "Visa medlem",
                "items" => $memberData['medlem'],
                "roles" => $memberData['roller'],
                'seglingar' => $memberData['seglingar'],
                'betalningar' => $memberData['betalningar'],
                'formAction' => $this->createUrl('medlem-update', ['id' => $id]),
                'createBetalningAction' => $this->createUrl('betalning-medlem', ['id' => $id]),
                'listBetalningAction' => $this->createUrl('betalning-medlem', ['id' => $id]),
                'deleteAction' => $this->createUrl('medlem-delete')
            ];

            return $this->view->render('viewMedlemEdit', $data);
        } catch (Exception $e) {
            return $this->redirectWithError('medlem-list', 'Kunde inte hämta medlem!');
        }
    }

    /**
     * Saves changes to a member.
     *
     * Sanitizes input data, updates the member record, and redirects to the member list.
     *
     * @param ServerRequestInterface $request The request object
     * @param array $params The route parameters, must contain 'id'
     */
    public function update(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $id = (int) $params['id'];
        $postData = $this->request->getParsedBody();

        $result = $this->medlemService->updateMember($id, $postData);

        if ($result->success) {
            return $this->redirectWithSuccess($result->redirectRoute, $result->message);
        } else {
            return $this->redirectWithError($result->redirectRoute, $result->message);
        }
    }

    /**
     * Prepares and displays the form for adding a new member.
     *
     * This method:
     * 1. Retrieves all available roles from the Roll model.
     * 2. Prepares data for the view
     * 3. Renders the 'viewMedlemNew' template with the prepared data.
     *
     * @return ResponseInterface
     */
    public function showNewForm(): ResponseInterface
    {
        $roller = $this->medlemService->getAllRoles();

        $data = [
            "title" => "Lägg till medlem",
            "roller" => $roller,
            'formAction' => $this->createUrl('medlem-create')
        ];
        return $this->view->render('viewMedlemNew', $data);
    }

    /**
     * Inserts a new member into the system.
     *
     * This method handles the creation of a new member based on submitted POST data.
     * It performs the following steps:
     * 1. Creates a new Medlem object.
     * 2. Prepares and sanitizes the submitted member data.
     * 3. Attempts to create the new member record in the database.
     * 4. Sets a flash message indicating the result of the operation.
     * 5. Redirects to the member list page.
     *
     * @return ResponseInterface
     * @throws Exception If there's an error during the member creation process
     */
    public function create(): ResponseInterface
    {
        $postData = $this->request->getParsedBody();

        $result = $this->medlemService->createMember($postData);

        if ($result->success) {
            return $this->redirectWithSuccess($result->redirectRoute, $result->message);
        } else {
            return $this->redirectWithError($result->redirectRoute, $result->message);
        }
    }

    /**
     * Deletes a member from the system.
     *
     * This method handles the deletion of a member based on the provided ID.
     * It attempts to delete the member and sets a flash message indicating
     * the result of the operation. After deletion, it redirects to the member list.
     *
     * @throws Exception If there's an error during the deletion process
     */
    public function delete(): ResponseInterface
    {
        $id = (int) $this->request->getParsedBody()['id'];

        $result = $this->medlemService->deleteMember($id);

        if ($result->success) {
            return $this->redirectWithSuccess($result->redirectRoute, $result->message);
        } else {
            return $this->redirectWithError($result->redirectRoute, $result->message);
        }
    }
}
