<?php

declare(strict_types=1);

namespace App\Controllers;

use AltoRouter;
use Exception;
use App\Models\Medlem;
use App\Models\MedlemRepository;
use App\Models\Roll;
use App\Models\BetalningRepository;
use App\Utils\Sanitizer;
use App\Utils\View;
use App\Utils\Session;
use App\Application;
use Psr\Http\Message\ServerRequestInterface;

/**
 * MedlemController handles operations related to members (medlemmar).
 *
 * This controller manages CRUD operations for members, including listing,
 * editing, creating, and deleting member records. It also handles related
 * operations such as managing roles and payments for members.
 */
class MedlemController extends BaseController
{
    /**
     * @var View The view object for rendering templates
     */
    private View $view;

    /**
     * Constructs a new MedlemController instance.
     *
     * @param Application $app The application instance
     * @param ServerRequestInterface $request The request object
     */
    public function __construct(Application $app, ServerRequestInterface $request)
    {
        parent::__construct($app, $request);
        $this->view = new View($this->app);
    }

    //Sanitizing rules for sanitizing user input for Medlem data
    private array $sanitizerRules = [
        'id' => 'int',
        'fodelsedatum' => ['date', 'Y-m-d'],
        'fornamn' => 'string',
        'efternamn' => 'string',
        'email' => 'email',
        'mobil' => 'string',
        'telefon' => 'string',
        'adress' => 'string',
        'postnummer' => 'string',
        'postort' => 'string',
        'kommentar' => 'string',
        'godkant_gdpr' => 'bool',
        'pref_kommunikation' => 'bool',
        'isAdmin' => 'bool',
        'foretag' => 'bool',
        'standig_medlem' => 'bool',
        'skickat_valkomstbrev' => 'bool',
    ];

    /**
     * Lists all members.
     *
     * Fetches all members from the repository and renders them in a view.
     */
    public function listAll(): void
    {
        $medlemRepo = new MedlemRepository($this->conn, $this->app);
        $result = $medlemRepo->getAll();

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
        $medlemRepo = new MedlemRepository($this->conn, $this->app);
        $result = $medlemRepo->getAll();
        $this->jsonResponse($result);
        exit;
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
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Kunde inte hämta medlem!'];
            $redirectUrl = $this->app->getRouter()->generate('medlem-list');
            header('Location: ' . $redirectUrl);
            exit;
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

        $result = $this->prepareAndSanitizeMedlemData($medlem, $postData);

        ///If the sanitization fails, just exit
        if (!$result) {
            exit;
        }

        try {
            $medlem->save();

            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Medlem ' . $medlem->fornamn . ' ' . $medlem->efternamn . ' uppdaterad!'
            ];
        } catch (Exception $e) {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Kunde inte uppdatera medlem! Fel: ' . $e->getMessage()
            ];
        }

        // Set the URL and redirect
        $redirectUrl = $this->app->getRouter()->generate('medlem-list');
        header('Location: ' . $redirectUrl);
        exit;
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

        $result = $this->prepareAndSanitizeMedlemData($medlem, $postData);
        //If the sanitize function returns false, we have an error, just exit
        if (!$result) {
            exit;
        }

        try {
            $medlem->create();
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Medlem ' . $medlem->fornamn . ' ' . $medlem->efternamn . ' skapad!'
            ];
        } catch (Exception $e) {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Kunde inte skapa medlem!'
            ];
        }

        // Set the URL and redirect
        $redirectUrl = $this->app->getRouter()->generate('medlem-list');
        header('Location: ' . $redirectUrl);
        exit;
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
        $id = $this->request->getParsedBody()['id'];
        try {
            $medlem = new Medlem($this->conn, $this->app->getLogger(), $id);
            $medlem->delete();
            $_SESSION['flash_message'] = ['type' => 'ok', 'message' => 'Medlem borttagen!'];
            $this->app->getLogger()->info('Medlem ' . $medlem->fornamn . ' ' . $medlem->efternamn . ' borttagen av: ' . Session::get('user_id'));
            // Set the URL and redirect
            $redirectUrl = $this->app->getRouter()->generate('medlem-list');
            header('Location: ' . $redirectUrl);
            exit;
        } catch (Exception $e) {
            $this->app->getLogger()->warning('Kunde inte ta bort medlem: ' . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Kunde inte ta bort medlem!'];
            $redirectUrl = $this->app->getRouter()->generate('medlem-list');
        }
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Prepares and sanitizes member data from POST input.
     *
     * Validates required fields, sanitizes input, and sets values on the Medlem object.
     *
     * @param Medlem $medlem The member object to update
     * @param array $postData The POST data to process
     * @return bool True if preparation was successful, false otherwise
     */
    private function prepareAndSanitizeMedlemData(Medlem $medlem, array $postData): bool
    {
        $errors = [];
        $requiredFields = ['fornamn', 'efternamn', 'fodelsedatum'];
        $booleanFields = ['godkant_gdpr', 'pref_kommunikation', 'isAdmin', 'foretag', 'standig_medlem', 'skickat_valkomstbrev'];

        //Sanitize user input
        $sanitizer = new Sanitizer();
        $cleanValues = $sanitizer->sanitize($postData, $this->sanitizerRules);

        //Save roller because the sanitizer removes them from the array
        $roller = $postData['roller'];

        // Validate required fields
        foreach ($requiredFields as $field) {
            if (empty($cleanValues[$field])) {
                $errors[] = $field;
            }
        }
        // If there were errors show a flash message and redirect back to the form
        if ($errors) {
            $errorMsg = "Följande obligatoriska fält måste fyllas i: " . implode(', ', $errors);
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => $errorMsg];
            if (isset($medlem->id)) {
                $redirectUrl = $this->app->getRouter()->generate('medlem-edit', ['id' => $medlem->id]);
            } else {
                $redirectUrl = $this->app->getRouter()->generate('medlem-new');
            }
            header('Location: ' . $redirectUrl);
            return false;
        }

        //Loop over everything in POST and set values on the Medlem object
        foreach ($cleanValues as $key => $value) {
            if (property_exists($medlem, $key) && !in_array($key, $booleanFields)) {
                // Assign value to corresponding property, checkoxes are handled below
                $medlem->$key = $value;
            }
        }

        // If checkboxes are not checked they don't exist in $_POST so set to False/0
        foreach ($booleanFields as $field) {
            $medlem->$field = isset($cleanValues[$field]) ? true : false;
        }

        //Update roles on the Medlem object
        if (isset($roller)) {
            $medlem->updateMedlemRoles($roller);
        }

        return true;
    }
}
