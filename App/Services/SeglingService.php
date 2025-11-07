<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use App\Models\SeglingRepository;
use App\Models\BetalningRepository;
use App\Models\MedlemRepository;
use App\Models\Segling;
use App\Models\Roll;
use App\Utils\Sanitizer;
use App\Utils\Session;
use Monolog\Logger;
use PDOException;

class SeglingService
{
    public function __construct(
        private SeglingRepository $seglingRepo,
        private BetalningRepository $betalningRepo,
        private MedlemRepository $medlemRepo,
        private Roll $roll,
        private Logger $logger
    ) {
    }

    /**
     * Retrieves all seglingar with participants.
     *
     * @return array<int, Segling> Array of all segling objects with participants
     */
    public function getAllSeglingar(): array
    {
        return $this->seglingRepo->getAllWithDeltagare();
    }

    /**
     * Gets all data needed for segling edit form.
     *
     * @param int $id Segling ID
     * @return array<string, mixed> Array containing segling, roles, and member lists
     * @throws Exception If segling not found
     */
    public function getSeglingEditData(int $id): array
    {
        $segling = $this->seglingRepo->getByIdWithDeltagare($id);
        if (!$segling) {
            throw new Exception('Segling not found');
        }

        // Get deltagare with payment status
        $year = (int) substr($segling->start_dat, 0, 4);
        /** @var array<int, array<string, mixed>> $deltagareWithBetalning */
        $deltagareWithBetalning = [];

        foreach ($segling->deltagare as $deltagare) {
            $hasPayed = $this->betalningRepo->memberHasPayed($deltagare['medlem_id'], $year);
            $deltagare['har_betalt'] = $hasPayed;
            $deltagareWithBetalning[] = $deltagare;
        }

        $segling->deltagare = $deltagareWithBetalning;

        return [
            'segling' => $segling,
            'roles' => $this->roll->getAll(),
            'allaSkeppare' => $this->medlemRepo->findMembersByRollName('Skeppare'),
            'allaBatsman' => $this->medlemRepo->findMembersByRollName('Båtsman'),
            'allaKockar' => $this->medlemRepo->findMembersByRollName('Kock')
        ];
    }

    /**
     * Updates an existing segling with form data.
     *
     * @param int $id Segling ID to update
     * @param array<string, mixed> $postData Form data from POST request
     * @return SeglingServiceResult Result object with success status and redirect info
     */
    public function updateSegling(int $id, array $postData): SeglingServiceResult
    {
        $sanitizer = new Sanitizer();
        $rules = [
            'startdat' => ['date', 'Y-m-d'],
            'slutdat' => ['date', 'Y-m-d'],
            'skeppslag' => 'string',
            'kommentar' => 'string',
        ];
        $cleanValues = $sanitizer->sanitize($postData, $rules);

        if ($this->seglingRepo->update($id, $cleanValues)) {
            return new SeglingServiceResult(true, 'Segling uppdaterad!', 'segling-list');
        } else {
            return new SeglingServiceResult(false, 'Kunde inte uppdatera seglingen. Försök igen.');
        }
    }

    /**
     * Deletes a segling by ID.
     *
     * @param int $id Segling ID to delete
     * @return SeglingServiceResult Result object with success status and redirect info
     */
    public function deleteSegling(int $id): SeglingServiceResult
    {
        if ($this->seglingRepo->delete($id)) {
            $this->logger->info('Segling was deleted: ' . $id . ' by user: ' . Session::get('user_id'));
            return new SeglingServiceResult(true, 'Seglingen är nu borttagen!', 'segling-list');
        } else {
            $this->logger->warning('Failed to delete segling: ' . $id . ' User: ' . Session::get('user_id'));
            return new SeglingServiceResult(false, 'Kunde inte ta bort seglingen. Försök igen.', 'segling-list');
        }
    }

    /**
     * Creates a new segling with form data.
     *
     * @param array<string, mixed> $postData Form data from POST request
     * @return SeglingServiceResult Result object with success status and redirect info
     */
    public function createSegling(array $postData): SeglingServiceResult
    {
        $sanitizer = new Sanitizer();
        $rules = [
            'startdat' => ['date', 'Y-m-d'],
            'slutdat' => ['date', 'Y-m-d'],
            'skeppslag' => 'string',
            'kommentar' => 'string',
        ];
        $cleanValues = $sanitizer->sanitize($postData, $rules);

        // Validate required fields
        if (empty($cleanValues['startdat']) || empty($cleanValues['slutdat']) || empty($cleanValues['skeppslag'])) {
            return new SeglingServiceResult(false, 'Indata saknades. Kunde inte spara seglingen. Försök igen.', 'segling-show-create');
        }

        $result = $this->seglingRepo->create($cleanValues);

        if ($result) {
            return new SeglingServiceResult(true, 'Seglingen är nu skapad!', 'segling-edit', $result);
        } else {
            return new SeglingServiceResult(false, 'Kunde inte spara till databas. Försök igen.', 'segling-show-create');
        }
    }

    /**
     * Adds a member to a segling with optional role.
     *
     * @param array<string, mixed> $postData Form data containing segling_id, segling_person, and optional segling_roll
     * @return SeglingServiceResult Result object with success status and message
     */
    public function addMemberToSegling(array $postData): SeglingServiceResult
    {
        if (!isset($postData['segling_id']) || !isset($postData['segling_person'])) {
            return new SeglingServiceResult(false, 'Missing input');
        }

        $seglingId = (int) $postData['segling_id'];
        $memberId = (int) $postData['segling_person'];
        $roleId = isset($postData['segling_roll']) ? (int) $postData['segling_roll'] : null;

        if ($this->seglingRepo->isMemberOnSegling($seglingId, $memberId)) {
            return new SeglingServiceResult(false, 'Medlemmen är redan tillagd på seglingen.');
        }

        try {
            if ($this->seglingRepo->addMemberToSegling($seglingId, $memberId, $roleId)) {
                return new SeglingServiceResult(true, 'Medlem tillagd på segling');
            } else {
                return new SeglingServiceResult(false, 'Failed to insert row');
            }
        } catch (PDOException $e) {
            return new SeglingServiceResult(false, 'PDO error: ' . $e->getMessage());
        }
    }

    /**
     * Removes a member from a segling.
     *
     * @param array<string, mixed> $data Data containing segling_id and medlem_id
     * @return SeglingServiceResult Result object with success status and message
     */
    public function removeMemberFromSegling(array $data): SeglingServiceResult
    {
        $seglingId = $data['segling_id'] ?? null;
        $medlemId = $data['medlem_id'] ?? null;

        if (!$seglingId || !$medlemId) {
            $this->logger->warning("Failed to delete medlem from segling. Invalid data. Medlem: " . $medlemId . " Segling: " . $seglingId);
            return new SeglingServiceResult(false, 'Invalid data');
        }

        if ($this->seglingRepo->removeMemberFromSegling((int) $seglingId, (int) $medlemId)) {
            $this->logger->info("Delete medlem from segling. Medlem: " . $medlemId . " Segling: " . $seglingId . " User: " . Session::get('user_id'));
            return new SeglingServiceResult(true, 'Member removed successfully');
        } else {
            $this->logger->warning("Failed to delete medlem from segling. Medlem: " . $medlemId . " Segling: " . $seglingId);
            return new SeglingServiceResult(false, 'Deletion failed');
        }
    }
}
