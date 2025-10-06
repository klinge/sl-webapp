<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use App\Models\MedlemRepository;
use App\Models\RollRepository;
use App\Models\BetalningRepository;
use App\Services\MailAliasService;
use App\Services\MedlemDataValidatorService;
use App\Utils\Session;
use App\Application;
use Monolog\Logger;

class MedlemService
{
    public function __construct(
        private MedlemRepository $medlemRepo,
        private BetalningRepository $betalningRepo,
        private RollRepository $rollRepo,
        private MedlemDataValidatorService $validator,
        private MailAliasService $mailAliasService,
        private Application $app,
        private Logger $logger
    ) {
    }

    public function getAllMembers(): array
    {
        return $this->medlemRepo->getAll();
    }

    public function getMemberEditData(int $id): array
    {
        $medlem = $this->medlemRepo->getById($id);
        if (!$medlem) {
            throw new Exception('Medlem not found');
        }

        return [
            'medlem' => $medlem,
            'roller' => $this->rollRepo->getAll(),
            'seglingar' => $this->medlemRepo->getSeglingarByMemberId($id),
            'betalningar' => $this->betalningRepo->getBetalningForMedlem($id)
        ];
    }

    public function getAllRoles(): array
    {
        return $this->rollRepo->getAll();
    }

    public function updateMember(int $id, array $postData): MedlemServiceResult
    {
        $medlem = $this->medlemRepo->getById($id);
        if (!$medlem) {
            return new MedlemServiceResult(
                false,
                'Medlem not found',
                'medlem-list'
            );
        }

        if (!$this->validator->validateAndPrepare($medlem, $postData)) {
            return new MedlemServiceResult(
                false,
                '',
                'medlem-edit'
            );
        }

        if (!$this->medlemRepo->save($medlem)) {
            return new MedlemServiceResult(
                false,
                'Kunde inte uppdatera medlem!',
                'medlem-list'
            );
        }

        if ($this->validator->hasEmailChanged()) {
            $this->updateEmailAliases();
        }

        return new MedlemServiceResult(
            true,
            'Medlem ' . $medlem->fornamn . ' ' . $medlem->efternamn . ' uppdaterad!',
            'medlem-list'
        );
    }

    public function createMember(array $postData): MedlemServiceResult
    {
        $medlem = $this->medlemRepo->createNew();

        if (!$this->validator->validateAndPrepare($medlem, $postData)) {
            return new MedlemServiceResult(
                false,
                '',
                'medlem-new'
            );
        }

        if (!$this->medlemRepo->save($medlem)) {
            return new MedlemServiceResult(
                false,
                'Kunde inte skapa medlem!',
                'medlem-create'
            );
        }

        $this->updateEmailAliases();

        return new MedlemServiceResult(
            true,
            'Medlem ' . $medlem->fornamn . ' ' . $medlem->efternamn . ' skapad!',
            'medlem-list'
        );
    }

    public function deleteMember(int $id): MedlemServiceResult
    {
        $medlem = $this->medlemRepo->getById($id);
        if (!$medlem) {
            return new MedlemServiceResult(
                false,
                'Medlem not found',
                'medlem-list'
            );
        }

        if (!$this->medlemRepo->delete($medlem)) {
            return new MedlemServiceResult(
                false,
                'Kunde inte ta bort medlem!',
                'medlem-list'
            );
        }

        $this->updateEmailAliases();
        $this->logger->info('Medlem ' . $medlem->fornamn . ' ' . $medlem->efternamn . ' borttagen av: ' . Session::get('user_id'));

        return new MedlemServiceResult(
            true,
            'Medlem borttagen!',
            'medlem-list'
        );
    }

    private function updateEmailAliases(): void
    {
        if ($this->app->getConfig('SMARTEREMAIL_ENABLED')) {
            $mailAlias = $this->app->getConfig('SMARTEREMAIL_ALIASNAME');
            $allEmails = array_column($this->medlemRepo->getEmailForActiveMembers(), 'email');
            $this->mailAliasService->updateAlias($mailAlias, $allEmails);
        }
    }
}
