<?php

declare(strict_types=1);

namespace App\Controllers;

use Exception;
use App\Models\Medlem;
use App\Models\MedlemRepository;
use App\Models\Roll;
use App\Models\BetalningRepository;
use App\Utils\View;
use App\Utils\Session;
use App\Services\MailAliasService;
use App\Services\MedlemDataValidatorService;
use App\Application;
use App\Traits\ResponseFormatter;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use PDO;
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

    private View $view;
    private MailAliasService $mailAliasService;
    private MedlemRepository $medlemRepo;
    private BetalningRepository $betalningRepo;
    private MedlemDataValidatorService $validator;
    private PDO $conn;

    /**
     * Constructs a new MedlemController instance.
     *
     * @param Application $app The application instance
     * @param ServerRequestInterface $request The request object
     */
    public function __construct(
        Application $app,
        ServerRequestInterface $request,
        Logger $logger,
        PDO $conn,
        BetalningRepository $betalningRepository
    ) {
        parent::__construct($app, $request, $logger);
        $this->conn = $conn;
        $this->view = new View($this->app);
        $this->medlemRepo = new MedlemRepository($this->conn, $this->logger);
        $this->betalningRepo = $betalningRepository;
        $this->mailAliasService = new MailAliasService($this->logger, $this->app->getConfig(null));
        $this->validator = new MedlemDataValidatorService();
    }

    /**
     * Lists all members.
     *
     * Fetches all members from the repository and renders them in a view.
     */
    public function listAll(): ResponseInterface
    {
        $result = $this->medlemRepo->getAll();

        //Put everyting in the data variable that is used by the view
        $data = [
            "title" => "Medlemmar",
            "items" => $result,
            'newAction' => $this->createUrl('medlem-new')
        ];
        return $this->view->render('viewMedlem', $data);
    }

    public function listJson(): ResponseInterface
    {
        $result = $this->medlemRepo->getAll();
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

        //Fetch member data
        try {
            $medlem = new Medlem($this->conn, $this->logger, $id);
            $roll = new Roll($this->conn, $this->logger);
            //Fetch roles and seglingar to use in the view
            $roller = $roll->getAll();
            $seglingar = $medlem->getSeglingar();
            //fetch betalningar for member
            $betalningar = $this->betalningRepo->getBetalningForMedlem($id);

            $data = [
                "title" => "Visa medlem",
                "items" => $medlem,
                "roles" => $roller,
                'seglingar' => $seglingar,
                'betalningar' => $betalningar,
                //Used in the view to set the proper action url for the form
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
        $medlem = new Medlem($this->conn, $this->logger, $id);
        $postData = $this->request->getParsedBody();

        if ($this->validator->validateAndPrepare($medlem, $postData)) {
            try {
                $medlem->save();
                if ($this->validator->hasEmailChanged()) {
                    $this->updateEmailAliases();
                }
                return $this->redirectWithSuccess(
                    'medlem-list',
                    'Medlem ' . $medlem->fornamn . ' ' . $medlem->efternamn . ' uppdaterad!'
                );
            } catch (Exception $e) {
                return $this->redirectWithError('medlem-list', 'Kunde inte uppdatera medlem! Fel: ' . $e->getMessage());
            }
        } else {
            //Error messages are set in the MedlemDataValidatorService so just redirect
            if (isset($medlem->id)) {
                return $this->redirectWithError('medlem-edit', '');
            } else {
                return $this->redirectWithError('medlem-new', '');
            }
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
        $roll = new Roll($this->conn, $this->logger);
        $roller = $roll->getAll();
        //Just show the form to add a new member
        $data = [
            "title" => "Lägg till medlem",
            "roller" => $roller,
            //Used in the view to set the proper action url for the form
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
        $medlem = new Medlem($this->conn, $this->logger);
        $postData = $this->request->getParsedBody();

        if ($this->validator->validateAndPrepare($medlem, $postData)) {
            try {
                $medlem->create();
                $this->updateEmailAliases();

                return $this->redirectWithSuccess(
                    'medlem-list',
                    'Medlem ' . $medlem->fornamn . ' ' . $medlem->efternamn . ' skapad!'
                );
            } catch (Exception $e) {
                return $this->redirectWithError(
                    'medlem-create',
                    'Kunde inte skapa medlem! Fel: ' . $e->getMessage()
                );
            }
        } else {
            //Error messages are set in the MedlemDataValidatorService so just redirect
            if (isset($medlem->id)) {
                return $this->redirectWithError('medlem-edit', '');
            } else {
                return $this->redirectWithError('medlem-new', '');
            }
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
        try {
            $medlem = new Medlem($this->conn, $this->logger, $id);
            $medlem->delete();
            $this->updateEmailAliases();

            $this->logger->info('Medlem ' . $medlem->fornamn . ' ' . $medlem->efternamn . ' borttagen av: ' . Session::get('user_id'));
            // Set the URL and redirect
            return $this->redirectWithSuccess('medlem-list', 'Medlem borttagen!');
        } catch (Exception $e) {
            $this->logger->warning('Kunde inte ta bort medlem: ' . $e->getMessage());
            return $this->redirectWithError('medlem-list', 'Kunde inte ta bort medlem! Fel: ' . $e->getMessage());
        }
    }

    public function updateEmailAliases(): void
    {
        if ($this->app->getConfig('SMARTEREMAIL_ENABLED')) {
            $mailAlias = $this->app->getConfig('SMARTEREMAIL_ALIASNAME');
            $allEmails = array_column($this->medlemRepo->getEmailForActiveMembers(), 'email');

            $this->mailAliasService->updateAlias($mailAlias, $allEmails);
        }
    }
}
