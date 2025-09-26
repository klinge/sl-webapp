<?php

declare(strict_types=1);

namespace App\Controllers;

use Exception;
use App\Models\Betalning;
use App\Models\BetalningRepository;
use App\Models\Medlem;
use App\Models\MedlemRepository;
use App\Utils\Sanitizer;
use App\Utils\View;
use App\Utils\Session;
use App\Utils\Email;
use App\Utils\EmailType;
use App\Application;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use PDO;
use Monolog\Logger;

class BetalningController extends BaseController
{
    private View $view;
    private BetalningRepository $betalningRepo;
    private MedlemRepository $medlemRepo;
    private ?string $welcomeEmailEnabled = "0";
    private PDO $conn;
    private Email $email;

    public function __construct(
        Application $app,
        ServerRequestInterface $request,
        Logger $logger,
        PDO $conn,
        BetalningRepository $betalningRepo,
        MedlemRepository $medlemRepo,
        Email $email
    ) {
        parent::__construct($app, $request, $logger);
        $this->conn = $conn;
        $this->view = new View($this->app);
        $this->betalningRepo = $betalningRepo;
        $this->medlemRepo = $medlemRepo;
        $this->email = $email;
        $this->welcomeEmailEnabled = $this->app->getConfig('WELCOME_MAIL_ENABLED');
    }

    public function list(): ResponseInterface
    {
        $result = $this->betalningRepo->getAllWithName();

        //Put everyting in the data variable that is used by the view
        $data = [
            "title" => "Betalningslista",
            "items" => $result
        ];
        return $this->view->render('viewBetalning', $data);
    }

    public function getBetalning(array $params): Betalning
    {
        $id = (int) $params['id'];
        $betalning = new Betalning($this->conn, $this->logger);
        $betalning->get($id);
        var_dump($betalning);
        //TODO add a view or modal to edit a payment..
        return $betalning;
    }

    public function getMedlemBetalning(array $params): ResponseInterface
    {
        $id = (int) $params['id'];
        $medlem = $this->medlemRepo->getById($id);
        if (!$medlem) {
            return $this->view->render('viewBetalning', [
                'success' => false,
                'title' => 'Medlem hittades inte'
            ]);
        }
        $namn = $medlem->getNamn();
        $result = $this->betalningRepo->getBetalningForMedlem($id);

        if (!empty($result)) {
            $data = [
                "success" => true,
                "title" => "Betalningar för: " . $namn,
                "items" => $result
            ];
        } else {
            $data = [
                "success" => false,
                "title" => "Inga betalningar hittades"
            ];
        }
        return $this->view->render('viewBetalning', $data);
    }
    //This function is called via a javascript fetch POST in the viewBetalning.php view
    public function createBetalning(array $params): ResponseInterface
    {
        $betalning = new Betalning($this->conn, $this->logger);
        $parsedBody = $this->request->getParsedBody();

        //Check for mandatory fields
        if (empty($parsedBody['belopp']) || empty($parsedBody['datum']) || empty($parsedBody['avser_ar'])) {
            // Handle missing values, e.g., return an error message or redirect to the form
            return $this->jsonResponse(['success' => false, 'message' => 'Belopp, datum, and avser_ar are required fields.']);
        }

        //Sanitize user input
        $sanitizer = new Sanitizer();
        $rules = [
            'medlem_id' => 'string',
            'datum' => ['date', 'Y-m-d'],
            'avser_ar' => ['date', 'Y'],
            'belopp' => 'float',
            'kommentar' => 'string',
        ];
        $cleanValues = $sanitizer->sanitize($parsedBody, $rules);

        $betalning->medlem_id = (int) $cleanValues['medlem_id'];
        $betalning->datum = $cleanValues['datum'];
        $betalning->belopp = (float) $cleanValues['belopp'];
        $betalning->avser_ar = (int) $cleanValues['avser_ar'];
        $betalning->kommentar = isset($cleanValues['kommentar']) ? $cleanValues['kommentar'] : '';

        $input_ok = $betalning->medlem_id && $betalning->datum && $betalning->belopp && $betalning->avser_ar;

        if (!$input_ok) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid input']);
        }

        // Create the betalning
        try {
            $result = $betalning->create();
            $sentMail = $this->sendWelcomeEmailOnFirstPayment($betalning->medlem_id);
            $this->logger->info('Betalning created successfully. Id of betalning: ' . $result['id'] .
                '. Registered by: ' . Session::get('user_id'));
            return $this->jsonResponse(['success' => true, 'message' => 'Betalning created successfully. Id of betalning: ' . $result['id']]);
        } catch (Exception $e) {
            $this->logger->warning('Error creating Betalning: ' . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Error creating Betalning: ' . $e->getMessage()]);
        }
    }

    public function deleteBetalning(array $params): ResponseInterface
    {
        $id = $params['id'];
        $betalning = new Betalning($this->conn, $this->logger);
        $betalning->get($id);
        try {
            $betalning->delete();
            return $this->jsonResponse(['success' => true, 'message' => 'Betalning deleted successfully']);
        } catch (Exception $e) {
            $this->logger->warning('Error deleting Betalning: ' . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Error deleting Betalning: ' . $e->getMessage()]);
        }
    }

    protected function sendWelcomeEmailOnFirstPayment(int $memberId): bool
    {
        if ($this->welcomeEmailEnabled !== "1") {
            $this->logger->info('sendWelcomeEmailOnFirstPayment: Sending mail is disabled');
            return false;
        }
        //Try to get member from repository, fail if not found
        $member = $this->medlemRepo->getById($memberId);
        if (!$member) {
            $this->logger->warning('sendWelcomeEmailonFirstPaymen: Member not found. MemberId: ' . $memberId);
            return false;
        }
        //Quit early if the member has already received the welcome mail
        if ($member->skickat_valkomstbrev) {
            return false;
        }
        //Fail if we don't have an email adress for the member
        if (empty($member->email)) {
            $this->logger->warning('sendWelcomeEmailOnFirstPaymen: No email adress for member. MemberId: ' . $memberId);
            return false;
        }
        //No welcome mail was sent and we have an email adress for the member
        $data = ['fornamn' => $member->fornamn, 'efternamn' => $member->efternamn];
        try {
            $this->email->send(EmailType::WELCOME, $member->email, 'Välkommen till föreningen Sofia Linnea', $data);
            $this->logger->info('sendWelcomeEmailOnFirstPayment: Welcome email sent to: ' . $member->email);
            //Update the status on the member
            $member->skickat_valkomstbrev = true;
            $member->save();
            return true;
        } catch (PHPMailerException $e) {
            $this->logger->warning('sendWelcomeEmailOnFirstPayment: Failed to send welcome email. Error: ' . $e->getMessage());
            return false;
        }
    }
}
