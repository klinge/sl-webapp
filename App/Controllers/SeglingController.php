<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\BetalningRepository;
use App\Models\SeglingRepository;
use App\Models\MedlemRepository;
use App\Models\Roll;
use App\Utils\Sanitizer;
use App\Utils\Session;
use App\Utils\View;
use App\Application;
use PDOException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Monolog\Logger;

class SeglingController extends BaseController
{
    private View $view;
    private SeglingRepository $seglingRepo;
    private BetalningRepository $betalningRepo;
    private MedlemRepository $medlemRepo;
    private Roll $roll;

    public function __construct(
        Application $app,
        ServerRequestInterface $request,
        Logger $logger,
        SeglingRepository $seglingRepo,
        MedlemRepository $medlemRepo,
        BetalningRepository $betalningsRepo,
        View $view,
        Roll $roll
    ) {
        parent::__construct($app, $request, $logger);
        $this->seglingRepo = $seglingRepo;
        $this->medlemRepo = $medlemRepo;
        $this->betalningRepo = $betalningsRepo;
        $this->view = $view;
        $this->roll = $roll;
    }

    public function list()
    {
        $result = $this->seglingRepo->getAllWithDeltagare();

        //Put everyting in the data variable that is used by the view
        $data = [
            "title" => "Bokningslista",
            "newAction" => $this->app->getRouter()->generate('segling-show-create'),
            "items" => $result
        ];
        $this->view->render('viewSegling', $data);
    }

    public function edit(array $params): ?ResponseInterface
    {
        $id = (int) $params['id'];
        $formAction = $this->app->getRouter()->generate('segling-save', ['id' => $id]);

        $segling = $this->seglingRepo->getById($id);
        if (!$segling) {
            return $this->notFoundResponse();
        }
        //Get all deltagare for this segling
        $segling->deltagare = $segling->getDeltagare();

        //Fetch payment status for deltagare and add to the $deltagare array
        $year = (int) substr($segling->start_dat, 0, 4);
        $deltagareWithBetalning = [];

        foreach ($segling->deltagare as $deltagare) {
            $hasPayed = $this->betalningRepo->memberHasPayed($deltagare['medlem_id'], $year);
            $deltagare['har_betalt'] = $hasPayed;
            $deltagareWithBetalning[] = $deltagare;
        }

        //Save the deltagare and betalning info in the $segling object
        $segling->deltagare = $deltagareWithBetalning;

        //Fetch all available roles
        $roller = $this->roll->getAll();

        //Fetch lists of persons who has a role to populate select boxes
        $allaSkeppare = $this->medlemRepo->getMembersByRollName('Skeppare');
        $allaBatsman = $this->medlemRepo->getMembersByRollName('Båtsman');
        $allaKockar = $this->medlemRepo->getMembersByRollName('Kock');

        $data = [
            "title" => "Visa segling",
            "items" => $segling,
            "roles" => $roller,
            "allaSkeppare" => $allaSkeppare,
            "allaBatsman" => $allaBatsman,
            "allaKockar" => $allaKockar,
            "formUrl" => $formAction
        ];
        $this->view->render('viewSeglingEdit', $data);
        return null;
    }

    public function save(array $params): ResponseInterface
    {
        $id = (int) $params['id'];

        $sanitizer = new Sanitizer();
        $rules = [
            'startdat' => ['date', 'Y-m-d'],
            'slutdat' => ['date', 'Y-m-d'],
            'skeppslag' => 'string',
            'kommentar' => 'string',
        ];
        $cleanValues = $sanitizer->sanitize($this->request->getParsedBody(), $rules);

        if ($this->seglingRepo->updateSegling($id, $cleanValues)) {
            Session::setFlashMessage('success', 'Segling uppdaterad!');
            $redirectUrl = $this->app->getRouter()->generate('segling-list');
            return new RedirectResponse($redirectUrl);
        } else {
            $return = ['success' => false, 'message' => 'Kunde inte uppdatera seglingen. Försök igen.'];
            return new JsonResponse($return);
        }
    }

    public function delete(array $params): ResponseInterface
    {
        $id = (int) $params['id'];

        if ($this->seglingRepo->deleteSegling($id)) {
            Session::setFlashMessage('success', 'Seglingen är nu borttagen!');
            $this->logger->info('Segling was deleted: ' . $id . ' by user: ' . Session::get('user_id'));
        } else {
            Session::setFlashMessage('error', 'Kunde inte ta bort seglingen. Försök igen.');
            $this->logger->warning('Failed to delete segling: ' . $id . ' User: ' . Session::get('user_id'));
        }
        $redirectUrl = $this->app->getRouter()->generate('segling-list');
        return new RedirectResponse($redirectUrl);
    }

    public function showCreate()
    {
        $formAction = $this->app->getRouter()->generate('segling-create');
        $data = [
            "title" => "Skapa ny segling",
            "formUrl" => $formAction
        ];
        $this->view->render('viewSeglingNew', $data);
    }

    public function create(): ResponseInterface
    {
        $sanitizer = new Sanitizer();
        $rules = [
            'startdat' => ['date', 'Y-m-d'],
            'slutdat' => ['date', 'Y-m-d'],
            'skeppslag' => 'string',
            'kommentar' => 'string',
        ];
        $cleanValues = $sanitizer->sanitize($this->request->getParsedBody(), $rules);

        //Check if requires indata is there, fail otherwise
        if (empty($cleanValues['startdat']) || empty($cleanValues['slutdat']) || empty($cleanValues['skeppslag'])) {
            Session::setFlashMessage('error', 'Indata saknades. Kunde inte spara seglingen. Försök igen.');
            $redirectUrl = $this->app->getRouter()->generate('segling-show-create');
            return new RedirectResponse($redirectUrl);
        }

        $result = $this->seglingRepo->createSegling($cleanValues);

        if ($result) {
            Session::setFlashMessage('success', 'Seglingen är nu skapad!');
            $redirectUrl = $this->app->getRouter()->generate('segling-edit', ['id' => $result]);
            return new RedirectResponse($redirectUrl);
        } else {
            Session::setFlashMessage('error', 'Kunde inte spara till databas. Försök igen.');
            $redirectUrl = $this->app->getRouter()->generate('segling-show-create');
            return new RedirectResponse($redirectUrl);
        }
    }

    /*
    * FUNCTIONS THAT HANDLE Members on a Segling
    * called from ajax calls in the client to return json data
    */
    public function saveMedlem(): ResponseInterface
    {
        $parsedBody = $this->request->getParsedBody();

        if (!isset($parsedBody['segling_id']) || !isset($parsedBody['segling_person'])) {
            return new JsonResponse(['success' => false, 'message' => 'Missing input']);
        }

        $seglingId = (int) $parsedBody['segling_id'];
        $memberId = (int) $parsedBody['segling_person'];
        $roleId = isset($parsedBody['segling_roll']) ? (int) $parsedBody['segling_roll'] : null;

        if ($this->seglingRepo->isMemberOnSegling($seglingId, $memberId)) {
            return new JsonResponse(['success' => false, 'message' => 'Medlemmen är redan tillagd på seglingen.']);
        }

        try {
            if ($this->seglingRepo->addMemberToSegling($seglingId, $memberId, $roleId)) {
                return new JsonResponse(['success' => true, 'message' => 'Medlem tillagd på segling']);
            } else {
                return new JsonResponse(['success' => false, 'message' => 'Failed to insert row']);
            }
        } catch (PDOException $e) {
            return new JsonResponse(['success' => false, 'message' => 'PDO error: ' . $e->getMessage()]);
        }
    }

    public function deleteMedlemFromSegling(): ResponseInterface
    {
        $data = $this->request->getParsedBody();

        $seglingId = $data['segling_id'] ?? null;
        $medlemId = $data['medlem_id'] ?? null;

        if (!$seglingId || !$medlemId) {
            $this->logger->warning("Failed to delete medlem from segling. Invalid data. Medlem: " . $medlemId . " Segling: " . $seglingId);
            return new JsonResponse(['status' => 'fail', 'error' => 'Invalid data']);
        }

        if ($this->seglingRepo->removeMemberFromSegling((int) $seglingId, (int) $medlemId)) {
            $this->logger->info("Delete medlem from segling. Medlem: " . $medlemId . " Segling: " . $seglingId . " User: " . Session::get('user_id'));
            return new JsonResponse(['status' => 'ok']);
        } else {
            $this->logger->warning("Failed to delete medlem from segling. Medlem: " . $medlemId . " Segling: " . $seglingId);
            return new JsonResponse(['status' => 'fail', 'error' => 'Deletion failed']);
        }
    }
}
