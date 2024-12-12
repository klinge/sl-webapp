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
use PDO;

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
    private MedlemDataValidatorService $validator;
    private PDO $conn;

    /**
     * Constructs a new MedlemController instance.
     *
     * @param Application $app The application instance
     * @param ServerRequestInterface $request The request object
     */
    public function __construct(Application $app, ServerRequestInterface $request, PDO $conn)
    {
        parent::__construct($app, $request);
        $this->conn = $conn;
        $this->view = new View($this->app);
        $this->medlemRepo = new MedlemRepository($this->conn, $this->app);
        $this->mailAliasService = new MailAliasService($this->app);
        $this->validator = new MedlemDataValidatorService();
    }

    /**
     * Lists all members.
     *
     * Fetches all members from the repository and renders them in a view.
     */
    public function listAll(): void
    {
        $result = $this->medlemRepo->getAll();

        //Put everyting in the data variable that is used by the view
        $data = [
            "title" => "Medlemmar",
            "items" => $result,
            'newAction' => $this->app->getRouter()->generate('medlem-new')
        ];
        $this->view->render('viewMedlem', $data);
    }

    public function listJson(): void
    {
        $result = $this->medlemRepo->getAll();
        $this->jsonResponse($result);
    }

    /**
     * Edits a specific member.
     *
     * Fetches member data, roles, sailings, and payments for the specified member ID
     * and renders them in an edit view.
     *
     * @param array $params The route parameters, must contain 'id'
     */
    public function edit(array $params): void
    {
        $id = (int) $params['id'];

        //Fetch member data
        try {
            $medlem = new Medlem($this->conn, $this->app->getLogger(), $id);
            $roll = new Roll($this->conn);
            //Fetch roles and seglingar to use in the view
            $roller = $roll->getAll();
            $seglingar = $medlem->getSeglingar();
            //fetch betalningar for member
            $betalRepo = new BetalningRepository($this->conn);
            $betalningar = $betalRepo->getBetalningForMedlem($id);

            $data = [
                "title" => "Visa medlem",
                "items" => $medlem,
                "roles" => $roller,
                'seglingar' => $seglingar,
                'betalningar' => $betalningar,
                //Used in the view to set the proper action url for the form
                'formAction' => $this->app->getRouter()->generate('medlem-update', ['id' => $id]),
                'createBetalningAction' => $this->app->getRouter()->generate('betalning-medlem', ['id' => $id]),
                'listBetalningAction' => $this->app->getRouter()->generate('betalning-medlem', ['id' => $id]),
                'deleteAction' => $this->app->getRouter()->generate('medlem-delete')
            ];

            $this->view->render('viewMedlemEdit', $data);
        } catch (Exception $e) {
            $this->redirectWithError('medlem-list', 'Kunde inte hämta medlem!');
            return;
        }
    }

    /**
     * Saves changes to a member.
     *
     * Sanitizes input data, updates the member record, and redirects to the member list.
     *
     * @param array $params The route parameters, must contain 'id'
     */
    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $medlem = new Medlem($this->conn, $this->app->getLogger(), $id);
        $postData = $this->request->getParsedBody();

        if ($this->validator->validateAndPrepare($medlem, $postData)) {
            try {
                $medlem->save();
                if ($this->validator->hasEmailChanged()) {
                    $this->updateEmailAliases();
                }
                $this->redirectWithSuccess(
                    'medlem-list',
                    'Medlem ' . $medlem->fornamn . ' ' . $medlem->efternamn . ' uppdaterad!'
                );
            } catch (Exception $e) {
                $this->redirectWithError('medlem-list', 'Kunde inte uppdatera medlem! Fel: ' . $e->getMessage());
            }
        } else {
            //Error messages are set in the MedlemDataValidatorService so just redirect
            if (isset($medlem->id)) {
                $redirectUrl = $this->app->getRouter()->generate('medlem-edit', ['id' => $medlem->id]);
            } else {
                $redirectUrl = $this->app->getRouter()->generate('medlem-new');
            }
            $this->emitRedirect($redirectUrl);
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
     * @return void
     */
    public function showNewForm(): void
    {
        $roll = new Roll($this->conn);
        $roller = $roll->getAll();
        //Just show the form to add a new member
        $data = [
            "title" => "Lägg till medlem",
            "roller" => $roller,
            //Used in the view to set the proper action url for the form
            'formAction' => $this->app->getRouter()->generate('medlem-create')
        ];
        $this->view->render('viewMedlemNew', $data);
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
     * @return void
     * @throws Exception If there's an error during the member creation process
     */
    public function create(): void
    {
        $medlem = new Medlem($this->conn, $this->app->getLogger());
        $postData = $this->request->getParsedBody();

        if ($this->validator->validateAndPrepare($medlem, $postData)) {
            try {
                $medlem->create();
                $this->updateEmailAliases();

                $this->redirectWithSuccess(
                    'medlem-list',
                    'Medlem ' . $medlem->fornamn . ' ' . $medlem->efternamn . ' skapad!'
                );
            } catch (Exception $e) {
                $this->redirectWithError(
                    'medlem-create',
                    'Kunde inte skapa medlem! Fel: ' . $e->getMessage()
                );
            }
        } else {
            //Error messages are set in the MedlemDataValidatorService so just redirect
            if (isset($medlem->id)) {
                $redirectUrl = $this->app->getRouter()->generate('medlem-edit', ['id' => $medlem->id]);
            } else {
                $redirectUrl = $this->app->getRouter()->generate('medlem-new');
            }
            $this->emitRedirect($redirectUrl);
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
    public function delete(): void
    {
        $id = (int) $this->request->getParsedBody()['id'];
        try {
            $medlem = new Medlem($this->conn, $this->app->getLogger(), $id);
            $medlem->delete();
            $this->updateEmailAliases();

            $this->app->getLogger()->info('Medlem ' . $medlem->fornamn . ' ' . $medlem->efternamn . ' borttagen av: ' . Session::get('user_id'));
            // Set the URL and redirect
            $this->redirectWithSuccess('medlem-list', 'Medlem borttagen!');
        } catch (Exception $e) {
            $this->app->getLogger()->warning('Kunde inte ta bort medlem: ' . $e->getMessage());
            $this->redirectWithError('medlem-list', 'Kunde inte ta bort medlem! Fel: ' . $e->getMessage());
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
