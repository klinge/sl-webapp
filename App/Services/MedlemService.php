<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use App\Models\MedlemRepository;
use App\Models\RollRepository;
use App\Models\BetalningRepository;
use App\Models\Medlem;
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

    /**
     * Retrieves all members.
     *
     * @return array<int, Medlem> Array of all member objects
     */
    public function getAllMembers(): array
    {
        return $this->medlemRepo->getAll();
    }

    /**
     * Gets all data needed for member edit form.
     *
     * @param int $id Member ID
     * @return array<string, mixed> Array containing member, roles, seglingar, and betalningar
     * @throws Exception If member not found
     */
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

    /**
     * Retrieves all available roles.
     *
     * @return array<int, array<string, mixed>> Array of all role data
     */
    public function getAllRoles(): array
    {
        return $this->rollRepo->getAll();
    }

    /**
     * Updates an existing member with form data.
     *
     * @param int $id Member ID to update
     * @param array<string, mixed> $postData Form data from POST request
     * @return MedlemServiceResult Result object with success status and redirect info
     */
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

    /**
     * Creates a new member with form data.
     *
     * @param array<string, mixed> $postData Form data from POST request
     * @return MedlemServiceResult Result object with success status and redirect info
     */
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

    /**
     * Deletes a member by ID.
     *
     * @param int $id Member ID to delete
     * @return MedlemServiceResult Result object with success status and redirect info
     */
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

    /**
     * Updates email aliases if SmarterEmail integration is enabled.
     *
     * @return void
     */
    private function updateEmailAliases(): void
    {
        if ($this->app->getConfig('SMARTEREMAIL_ENABLED')) {
            $mailAlias = $this->app->getConfig('SMARTEREMAIL_ALIASNAME');
            /** @var array<int, string> $allEmails */
            $allEmails = array_column($this->medlemRepo->getEmailForActiveMembers(), 'email');
            $this->mailAliasService->updateAlias($mailAlias, $allEmails);
        }
    }
}
